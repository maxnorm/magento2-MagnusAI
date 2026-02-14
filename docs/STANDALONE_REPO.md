# Extracting Magnus Assistant as a Standalone Repository

This guide describes how to move `Magnus_Assistant` from `app/code/Magnus/Assistant` into its own Git repository so it can be installed via Composer in any Magento 2 project.

## 1. Repository structure

**Rule:** The **contents** of the current `Assistant` folder become the **root** of the new repository. There is no `Magnus/` or `Assistant/` wrapper directory in the new repo.

- New repo root = current `app/code/Magnus/Assistant/` contents.
- So at the root of the new repo you will have: `Api/`, `Model/`, `Block/`, `Controller/`, `etc/`, `view/`, `Test/`, `registration.php`, `composer.json`, `README.md`, etc.

## 2. Create the new repository

1. **Create a new empty Git repository** (e.g. `magnus-module-assistant`) on GitHub, GitLab, or your host.

2. **Clone it locally** and copy the module contents into the repo root:

   ```bash
   git clone <your-repo-url> magnus-module-assistant
   cd magnus-module-assistant

   # Copy everything from inside Assistant (not the Assistant folder itself)
   rsync -av --exclude='.git' \
     /path/to/londerosports/app/code/Magnus/Assistant/ .
   ```

   Or from the londerosports repo:

   ```bash
   cd /path/to/londerosports
   cp -r app/code/Magnus/Assistant/* /path/to/magnus-module-assistant/
   ```

3. **Add a `.gitignore`** in the new repo root (see section 5 below).

4. **Optional:** Align `composer.json` with `etc/module.xml` (see section 4).

5. **Commit and push:**

   ```bash
   cd /path/to/magnus-module-assistant
   git add .
   git commit -m "Initial standalone module"
   git push -u origin main
   ```

## 3. What to exclude from the new repo (optional)

- **`docs/`** – Keep if you want the docs in the standalone module; remove or trim if you prefer a minimal repo (you can keep `STANDALONE_REPO.md` and `README.md`-related content).
- **Internal/audit docs** – Move or delete any project-specific audits or plans that don’t belong in a public/reusable module.

Everything under `Api/`, `Model/`, `Block/`, `Controller/`, `etc/`, `view/`, `Test/`, `registration.php`, and `composer.json` should be in the new repo.

## 4. Composer requirements (optional but recommended)

Your `etc/module.xml` declares a load order dependency on `Magento_CatalogRule` and `Magento_Store`. For a clean standalone package, add the corresponding Composer packages so installs resolve correctly:

In `composer.json`:

```json
"require": {
  "php": ">=8.1",
  "magento/framework": "*",
  "magento/module-backend": "*",
  "magento/module-config": "*",
  "magento/module-catalog-rule": "*",
  "magento/module-store": "*",
  "magento/module-user": "*"
}
```

Catalog and Sales are **optional** (tools check `moduleList->isEnabled()`), so they do not need to be in `require` unless you want to enforce them.

## 5. `.gitignore` for the new repo

Create `.gitignore` in the new repo root:

```gitignore
# IDE and OS
.idea/
.vscode/
*.swp
*.swo
.DS_Store
Thumbs.db

# Composer (if you ever run composer in the module repo)
/vendor/
composer.lock

# Magento (if someone runs Magento from the module repo by mistake)
/app/
/bin/
/dev/
/generated/
/lib/
/pub/
/setup/
/var/
.env
```

## 6. Installing the module in a Magento project

**Option A – VCS (e.g. GitHub)**

In the project’s `composer.json`:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/your-org/magnus-module-assistant" }
  ],
  "require": {
    "magnus/module-assistant": "^1.0"
  }
}
```

Then:

```bash
composer update magnus/module-assistant
bin/magento module:enable Magnus_Assistant
bin/magento setup:upgrade
```

**Option B – Private Composer repository**

Publish `magnus/module-assistant` to a private Satis/Packagist or private Composer repo and add that repository to the project’s `composer.json`, then require `magnus/module-assistant` as above.

**Option C – Local path (development)**

```json
"repositories": [
  { "type": "path", "url": "../magnus-module-assistant", "options": { "symlink": true } }
],
"require": {
  "magnus/module-assistant": "*"
}
```

## 7. After extraction

- **This repo (londerosports):** Remove or replace the in-repo module with Composer:

  ```bash
  composer require magnus/module-assistant
  ```

  Then remove `app/code/Magnus/Assistant` (and `app/code/Magnus` if it’s empty) so the project uses only the Composer-installed version.

- **CI:** If you have tests under `Test/`, add a minimal CI (e.g. GitHub Actions) in the new repo to run `composer install` and PHPUnit against a Magento test environment.

- **Versioning:** Use Git tags for releases (e.g. `1.0.0`) and keep `composer.json` `version` in sync so `^1.0` and similar constraints work as expected.

## Summary checklist

- [ ] Create new empty repo.
- [ ] Copy contents of `Assistant/` to repo root (no parent `Magnus/` or `Assistant/` folder).
- [ ] Add `.gitignore`.
- [ ] Optionally add `magento/module-catalog-rule` and `magento/module-store` to `composer.json` `require`.
- [ ] Commit and push.
- [ ] In Magento project: add VCS/path repo, `composer require magnus/module-assistant`, enable module, `setup:upgrade`.
- [ ] Remove `app/code/Magnus/Assistant` from this project once you rely on the Composer package.
