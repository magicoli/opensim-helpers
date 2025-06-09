# contrib/

This directory contains external files copied from separate projects.

**⚠️ DO NOT MODIFY FILES HERE**

Files are automatically copied by composer scripts and any changes will be lost on next update.

## magicoli/opensim-rest-php/

- **Project:** magicoli/opensim-rest-php
- **File:** class-rest.php
- **Copied by:** composer post-update-cmd script
- **Production:** File is committed to git for production deployments

### Workflow:
1. `composer update` copies latest file
2. Review changes: `git diff contrib/`
3. Commit if satisfied: `git add contrib/ && git commit -m "Update opensim-rest-php"`

### Note for myself

Consider the benefits of using Submodule + Sparse instead of composer:

```bash
# In engine/ directory
git submodule add https://github.com/magicoli/opensim-rest-php.git contrib/opensim-rest-php
cd contrib/opensim-rest-php
git config core.sparseCheckout true
echo "class-rest.php" > .git/info/sparse-checkout
echo "!*.exe" >> .git/info/sparse-checkout
echo "!*.dll" >> .git/info/sparse-checkout
git read-tree -m -u HEAD
```
