/**
 * Magnus Assistant chat UI - toggle panel, send messages, display history.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    var currentConversationId = null;

    function init() {
        var $trigger = $('#magnus-trigger');
        var $root = $('#magnus-panel-root');
        var $close = $('#magnus-panel-close');
        var $messages = $('#magnus-messages');
        var $input = $('#magnus-input');
        var $send = $('#magnus-send');
        var $suggestedQuestionsWrap = $('#magnus-suggested-questions-wrap');
        var $suggestedQuestions = $('#magnus-suggested-questions');
        var $suggestedPrev = $('#magnus-suggested-prev');
        var $suggestedNext = $('#magnus-suggested-next');

        if (!$root.length || !$trigger.length) {
            return;
        }

        var sendUrl = $root.data('magnus-send-url');
        var historyUrl = $root.data('magnus-history-url');
        var listUrl = $root.data('magnus-list-url');
        var formKey = $root.data('magnus-form-key');

        var $pastChatsTrigger = $('#magnus-past-chats-trigger');
        var $pastChatsDropdown = $('#magnus-past-chats-dropdown');
        var $pastChatsList = $('#magnus-past-chats-list');
        var $newChatBtn = $('#magnus-new-chat-btn');

        function showPanel() {
            $root.show();
            $input.focus();
            // Recompute slider arrow state now that panel is visible (sizes are correct)
            if (typeof updateSuggestedNavButtons === 'function') {
                setTimeout(updateSuggestedNavButtons, 50);
            }
        }

        function hidePanel() {
            $root.hide();
        }

        function formatUpdatedAt(updatedAtStr) {
            if (!updatedAtStr) {
                return '';
            }
            var d = new Date(updatedAtStr);
            if (isNaN(d.getTime())) {
                return updatedAtStr;
            }
            var now = new Date();
            var diffMs = now - d;
            var diffMins = Math.floor(diffMs / 60000);
            var diffHours = Math.floor(diffMs / 3600000);
            var diffDays = Math.floor(diffMs / 86400000);
            if (diffMins < 1) {
                return $.mage.__('Just now');
            }
            if (diffMins < 60) {
                return $.mage.__('%1 min ago').replace('%1', diffMins);
            }
            if (diffHours < 24) {
                return $.mage.__('%1 hours ago').replace('%1', diffHours);
            }
            if (diffDays < 7) {
                return $.mage.__('%1 days ago').replace('%1', diffDays);
            }
            return d.toLocaleDateString();
        }

        function formatShortTime(dateStr) {
            if (!dateStr) {
                return '';
            }
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) {
                return '';
            }
            return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
        }

        function closePastChatsDropdown() {
            $pastChatsDropdown.hide().attr('aria-hidden', 'true');
            if ($pastChatsTrigger.length) {
                $pastChatsTrigger.attr('aria-expanded', 'false');
            }
        }

        function togglePastChatsDropdown() {
            if ($pastChatsDropdown.is(':visible')) {
                closePastChatsDropdown();
                return;
            }
            $pastChatsDropdown.show().attr('aria-hidden', 'false');
            if ($pastChatsTrigger.length) {
                $pastChatsTrigger.attr('aria-expanded', 'true');
            }
            if ($pastChatsList.children().length === 0 && listUrl) {
                var url = listUrl + (listUrl.indexOf('?') >= 0 ? '&' : '?') + 'form_key=' + encodeURIComponent(formKey);
                $.getJSON(url).done(function (data) {
                    var conversations = data.conversations || [];
                    $pastChatsList.empty();
                    $.each(conversations, function (i, conv) {
                        var id = conv.id;
                        var title = (conv.title && String(conv.title).trim()) ? String(conv.title).trim() : $.mage.__('New conversation');
                        var relativeTime = formatUpdatedAt(conv.updated_at) || ('#' + id);
                        var shortTime = formatShortTime(conv.updated_at);
                        var timeLabel = relativeTime === $.mage.__('Just now') && shortTime
                            ? relativeTime + ' · ' + shortTime
                            : relativeTime;
                        var $li = $('<li class="magnus-past-chats-item"></li>');
                        var $btn = $('<button type="button" class="magnus-past-chats-item-btn"></button>')
                            .data('conversation-id', id);
                        $btn.append($('<span class="magnus-past-chats-item-title"></span>').text(title));
                        $btn.append($('<span class="magnus-past-chats-item-time"></span>').text(timeLabel));
                        $li.append($btn);
                        $pastChatsList.append($li);
                    });
                }).fail(function () {
                    $pastChatsList.append($('<li class="magnus-past-chats-item magnus-past-chats-error"></li>').text($.mage.__('Could not load conversations.')));
                });
            }
        }

        function loadConversationHistory(conversationId) {
            var url = historyUrl + (historyUrl.indexOf('?') >= 0 ? '&' : '?') + 'conversation_id=' + encodeURIComponent(conversationId);
            $.getJSON(url).done(function (data) {
                if (data.error) {
                    appendMessage('assistant', $.mage.__('Error: ') + data.error, null, null);
                    return;
                }
                currentConversationId = data.conversation_id;
                $messages.empty();
                if ($suggestedQuestionsWrap && $suggestedQuestionsWrap.length) {
                    $suggestedQuestionsWrap.hide();
                }
                var list = data.messages || [];
                $.each(list, function (i, msg) {
                    appendMessage(msg.role || 'user', msg.content || '', null, null);
                });
                if ($messages[0]) {
                    $messages.scrollTop($messages[0].scrollHeight);
                }
                closePastChatsDropdown();
            }).fail(function (xhr) {
                var err = xhr.responseJSON && xhr.responseJSON.error
                    ? xhr.responseJSON.error
                    : $.mage.__('Could not load conversation.');
                appendMessage('assistant', $.mage.__('Error: ') + err, null, null);
            });
        }

        function startNewChat() {
            currentConversationId = null;
            $messages.empty();
            if ($suggestedQuestionsWrap && $suggestedQuestionsWrap.length) {
                $suggestedQuestionsWrap.show();
            }
            closePastChatsDropdown();
        }

        function updateSuggestedNavButtons() {
            if (!$suggestedQuestions.length) {
                return;
            }
            var el = $suggestedQuestions[0];
            var maxScroll = el.scrollWidth - el.clientWidth;
            var atStart = el.scrollLeft <= 2;
            var atEnd = maxScroll <= 2 || el.scrollLeft >= maxScroll - 2;
            $suggestedPrev.prop('disabled', atStart);
            $suggestedNext.prop('disabled', atEnd);
        }

        /**
         * Format reply content: escape HTML, support newlines, markdown titles (##, ###), bold/italic.
         * Titles and bold/italic are applied so LLM output stays safe (heading text is escaped).
         */
        function formatReplyContent(content) {
            var lines = (content || '').split('\n');
            var result = [];
            for (var i = 0; i < lines.length; i++) {
                var line = lines[i];
                var escapedLine = $('<div>').text(line).html();
                if (/^## .+$/.test(line)) {
                    var h2Text = $('<div>').text(line.replace(/^## /, '')).html();
                    result.push('<h2 class="magnus-reply-heading">' + h2Text + '</h2>');
                } else if (/^### .+$/.test(line)) {
                    var h3Text = $('<div>').text(line.replace(/^### /, '')).html();
                    result.push('<h3 class="magnus-reply-heading">' + h3Text + '</h3>');
                } else {
                    result.push(escapedLine + '<br>');
                }
            }
            var joined = result.join('').replace(/<br>$/g, '');
            // Markdown bold **...** (before single * so ** is consumed first)
            joined = joined.replace(/\*\*([\s\S]+?)\*\*/g, '<strong>$1</strong>');
            joined = joined.replace(/\*([\s\S]+?)\*/g, '<em>$1</em>');
            return joined;
        }

        function appendMessage(role, content, actionProposal, structuredData, isDevError) {
            var roleClass = role === 'user' ? 'magnus-msg-user' : 'magnus-msg-assistant';
            var $msg = $('<div class="magnus-msg ' + roleClass + '"><div class="magnus-msg-content"></div></div>');
            var displayContent = role === 'assistant'
                ? formatReplyContent(content)
                : $('<div>').text(content).html().replace(/\n/g, '<br>');
            $msg.find('.magnus-msg-content').html(displayContent);
            
            // Add dev error class for styling
            if (isDevError) {
                $msg.addClass('magnus-msg-dev-error');
            }
            
            // Render structured data if present
            if (structuredData && structuredData.type) {
                var $structured = $('<div class="magnus-structured-data"></div>');
                
                if (structuredData.type === 'table' && structuredData.table) {
                    $structured.append(renderTable(structuredData.table));
                } else if (structuredData.type === 'steps' && structuredData.steps) {
                    $structured.append(renderSteps(structuredData.steps));
                } else if (structuredData.type === 'links' && structuredData.links) {
                    $structured.append(renderLinks(structuredData.links));
                }
                
                $msg.find('.magnus-msg-content').after($structured);
            }
            
            if (actionProposal) {
                $msg.addClass('magnus-msg-action-proposal');
                var $preview = $('<div class="magnus-action-preview"></div>');
                $preview.append($('<div class="magnus-action-summary"></div>').text(actionProposal.preview.summary || ''));
                
                if (actionProposal.preview.details && Object.keys(actionProposal.preview.details).length > 0) {
                    var $details = $('<div class="magnus-action-details"></div>');
                    $.each(actionProposal.preview.details, function(key, value) {
                        $details.append($('<div class="magnus-action-detail-item"><strong>' + key + ':</strong> ' + value + '</div>'));
                    });
                    $preview.append($details);
                }
                
                var $prompt = $('<div class="magnus-action-prompt"></div>');
                $prompt.text($.mage.__('Type "yes" to approve or "no" to cancel.'));
                $preview.append($prompt);
                
                $msg.find('.magnus-msg-content').after($preview);
            }
            
            $messages.append($msg);
            $messages.scrollTop($messages[0].scrollHeight);
        }

        function renderTable(tableData) {
            var $table = $('<table class="magnus-structured-table"></table>');
            var $thead = $('<thead></thead>');
            var $tbody = $('<tbody></tbody>');
            
            // Headers
            if (tableData.headers && tableData.headers.length > 0) {
                var $tr = $('<tr></tr>');
                $.each(tableData.headers, function(i, header) {
                    $tr.append($('<th></th>').text(header));
                });
                $thead.append($tr);
            }
            
            // Rows
            if (tableData.rows && tableData.rows.length > 0) {
                $.each(tableData.rows, function(i, row) {
                    var $tr = $('<tr></tr>');
                    $.each(row, function(j, cell) {
                        $tr.append($('<td></td>').text(cell));
                    });
                    $tbody.append($tr);
                });
            }
            
            $table.append($thead);
            $table.append($tbody);
            return $table;
        }

        function escapeAttr(str) {
            if (typeof str !== 'string') {
                return '';
            }
            return str
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function renderSteps(steps) {
            // Only show steps that have links, as "Quick actions" (no duplicate numbering from message)
            var $wrap = $('<div class="magnus-quick-actions"></div>');
            var $title = $('<div class="magnus-quick-actions-title"></div>').text($.mage.__('Quick actions'));
            var $list = $('<ul class="magnus-quick-actions-list"></ul>');
            $.each(steps, function(i, step) {
                if (!step.link) {
                    return;
                }
                var $li = $('<li class="magnus-quick-actions-item"></li>');
                var $link = $('<a target="_blank" class="magnus-quick-action-link"></a>')
                    .attr('href', step.link)
                    .text(step.text || $.mage.__('Open'))
                    .attr('title', step.text ? escapeAttr(step.text) : '');
                $li.append($link);
                $list.append($li);
            });
            if ($list.children().length === 0) {
                return $('<div></div>');
            }
            $wrap.append($title).append($list);
            return $wrap;
        }

        function renderLinks(links) {
            var $list = $('<ul class="magnus-structured-links"></ul>');
            $.each(links, function(i, link) {
                var $li = $('<li></li>');
                var $a = $('<a href="' + link.url + '" target="_blank"></a>')
                    .text(link.text || link.url);
                $li.append($a);
                $list.append($li);
            });
            return $list;
        }

        function formatDevErrorDetails(details) {
            var formatted = '\n\n[DEV MODE]\n';
            formatted += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
            
            if (details.message) {
                formatted += 'Message: ' + details.message + '\n';
            }
            if (details.file) {
                formatted += 'File: ' + details.file;
                if (details.line) {
                    formatted += ':' + details.line;
                }
                formatted += '\n';
            }
            if (details.code !== undefined && details.code !== null) {
                formatted += 'Code: ' + details.code + '\n';
            }
            if (details.previous) {
                formatted += '\nPrevious Exception:\n';
                if (details.previous.message) {
                    formatted += '  Message: ' + details.previous.message + '\n';
                }
                if (details.previous.file) {
                    formatted += '  File: ' + details.previous.file;
                    if (details.previous.line) {
                        formatted += ':' + details.previous.line;
                    }
                    formatted += '\n';
                }
            }
            if (details.trace) {
                formatted += '\nStack Trace:\n';
                formatted += details.trace.split('\n').map(function(line) {
                    return '  ' + line;
                }).join('\n');
            }
            
            formatted += '\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━';
            return formatted;
        }

        function setLoading(loading) {
            $send.prop('disabled', loading);
            if (loading) {
                $send.text($.mage.__('Sending...'));
            } else {
                $send.text($.mage.__('Send'));
            }
        }

        function sendMessage() {
            var text = $input.val().trim();
            if (!text) {
                return;
            }
            $input.val('');
            
            // Hide suggested questions after first message
            if ($suggestedQuestionsWrap && $suggestedQuestionsWrap.is(':visible')) {
                $suggestedQuestionsWrap.hide();
            }
            
            appendMessage('user', text);
            setLoading(true);

            var payload = {
                message: text,
                conversation_id: currentConversationId,
                context: {}
            };

            // Form key must be in URL: backend validates getParam('form_key'), which does not read JSON body
            var url = sendUrl + (sendUrl.indexOf('?') >= 0 ? '&' : '?') + 'isAjax=true&form_key=' + encodeURIComponent(formKey);
            var jsonData = JSON.stringify(payload);

            $.ajax({
                url: url,
                type: 'POST',
                contentType: 'application/json',
                data: jsonData,
                dataType: 'json',
                processData: false,
                // Override Magento's global beforeSend to prevent JSON corruption
                beforeSend: function(xhr, settings) {
                    // Only add isAjax flag, don't modify the JSON data
                    if (!settings.url.match(new RegExp('[?&]isAjax=true',''))) {
                        settings.url = settings.url.match(new RegExp('\\?', 'g')) ?
                            settings.url + '&isAjax=true' :
                            settings.url + '?isAjax=true';
                    }
                    // Ensure data remains as JSON string
                    settings.data = jsonData;
                }
            }).done(function (data) {
                if (data.error) {
                    // Handle both string and boolean error values
                    var errorText = typeof data.error === 'string' ? data.error : 
                                    (data.error === true ? $.mage.__('An error occurred. Please try again.') : String(data.error));
                    var errorMsg = $.mage.__('Error: ') + errorText;
                    var hasDevDetails = !!data.dev_details;
                    if (hasDevDetails) {
                        errorMsg += formatDevErrorDetails(data.dev_details);
                    }
                    appendMessage('assistant', errorMsg, null, null, hasDevDetails);
                } else {
                    if (data.conversation_id) {
                        currentConversationId = data.conversation_id;
                    }
                    appendMessage('assistant', data.reply || '', data.action_proposal || null, data.structured_data || null);
                }
            }).fail(function (xhr) {
                var err = xhr.responseJSON && xhr.responseJSON.error
                    ? xhr.responseJSON.error
                    : $.mage.__('Request failed. Please try again.');
                // When server returns non-JSON (e.g. HTML error page), show status for debugging
                if (!xhr.responseJSON && xhr.status) {
                    err += ' (HTTP ' + xhr.status + ')';
                    if (typeof console !== 'undefined' && console.warn) {
                        console.warn('Magnus chat request failed:', xhr.status, xhr.statusText, xhr.responseText ? xhr.responseText.substring(0, 200) : '');
                    }
                }
                var hasDevDetails = !!(xhr.responseJSON && xhr.responseJSON.dev_details);
                var errorMsg = err;
                if (hasDevDetails) {
                    errorMsg += formatDevErrorDetails(xhr.responseJSON.dev_details);
                }
                appendMessage('assistant', errorMsg, null, null, hasDevDetails);
            }).always(function () {
                setLoading(false);
            });
        }

        // Suggested questions click handler
        if ($suggestedQuestions.length) {
            $suggestedQuestions.on('click', '.magnus-suggested-question', function() {
                var question = $(this).data('question');
                if (question) {
                    $input.val(question);
                    sendMessage();
                }
            });
        }

        // Suggested questions slider prev/next – one question per click
        if ($suggestedQuestions.length) {
            var scrollEl = $suggestedQuestions[0];
            var $track = $suggestedQuestions.find('.magnus-suggested-questions-track');
            var gap = 12; /* matches .magnus-suggested-questions-track gap in CSS */
            function getScrollStep() {
                var $first = $track.children('.magnus-suggested-question').first();
                return $first.length ? $first.outerWidth() + gap : 280;
            }
            function scrollAfterNav() {
                setTimeout(updateSuggestedNavButtons, 50);
            }
            $suggestedPrev.on('click', function(e) {
                e.preventDefault();
                if ($suggestedPrev.is(':disabled')) {
                    return;
                }
                scrollEl.scrollBy({ left: -getScrollStep(), behavior: 'smooth' });
                scrollAfterNav();
            });
            $suggestedNext.on('click', function(e) {
                e.preventDefault();
                if ($suggestedNext.is(':disabled')) {
                    return;
                }
                scrollEl.scrollBy({ left: getScrollStep(), behavior: 'smooth' });
                scrollAfterNav();
            });
            $suggestedQuestions.on('scroll', updateSuggestedNavButtons);
            // Initial state; run again after layout so dimensions are correct
            updateSuggestedNavButtons();
            setTimeout(updateSuggestedNavButtons, 100);
        }

        $trigger.on('click', showPanel);
        $close.on('click', hidePanel);
        $send.on('click', sendMessage);
        $input.on('keydown', function (e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        if ($pastChatsTrigger.length) {
            $pastChatsTrigger.on('click', function (e) {
                e.stopPropagation();
                togglePastChatsDropdown();
            });
        }
        if ($newChatBtn.length) {
            $newChatBtn.on('click', startNewChat);
        }
        $pastChatsList.on('click', '.magnus-past-chats-item-btn', function () {
            var id = $(this).data('conversation-id');
            if (id) {
                loadConversationHistory(id);
            }
        });

        $(document).on('click', function (e) {
            if ($pastChatsDropdown.is(':visible') && $(e.target).closest('#magnus-past-chats-dropdown').length === 0 && $(e.target).closest('#magnus-past-chats-trigger').length === 0) {
                closePastChatsDropdown();
            }
        });

        // Close panel when clicking outside (backdrop / page content)
        $(document).on('click', function (e) {
            if (!$root.is(':visible')) {
                return;
            }
            var $target = $(e.target);
            if ($target.closest('#magnus-panel-root').length || $target.closest('#magnus-trigger').length) {
                return;
            }
            hidePanel();
        });
    }

    return function () {
        $(document).ready(init);
    };
});
