# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- Performance recorder system for Buy Now button on variable products
  - Playwright-based automated testing framework
  - Captures HAR files, console logs, performance traces, and event listeners
  - Deployment verification via `wp-content/deploy.txt` marker
  - CI/CD integration with automatic deploy marker creation
  - Local artifact storage in `perf/artifacts/`
  - Kill switch via `DISABLE_PERF_RECORDER` environment variable
- Project infrastructure files:
  - `claude.md` - Self-operating guide
  - `.gitignore` - Git ignore rules
  - `package.json` - Node.js dependencies and scripts
  - `tools/recorder/` - Performance recording scripts

### Changed
- Updated GitHub Actions workflows to create deployment marker after FTP upload
  - Modified `deploy-plugin.yml`
  - Modified `deploy-root-all-files.yml`

### Security
- No frontend code injection - recorder runs externally
- No sensitive data exposed in deploy markers
- Artifacts stored locally, not committed to repository