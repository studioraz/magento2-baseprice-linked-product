

## Releases

Releases follow the [shared Release Please guide](https://github.com/studioraz/ci-templates/blob/master/docs/release-please.md).
Use Conventional Commit PR titles (`feat: add support`, `fix: correct behavior`).
Squash merges use the PR title and body; single-commit PRs must have a matching commit title.

Pushes to `master`, the Monday 06:00 UTC schedule and manual runs maintain the release PR and synchronize shared configuration.
Merging a release PR creates a GitHub Release and an unprefixed SemVer tag, and updates the root `CHANGELOG.md`.
Enrollment preserves existing release history (verified baseline `1.0.1`).

The optional `RELEASE_TOKEN` is forwarded to the shared workflow. Without it, `GITHUB_TOKEN` is used and bot-created PRs do not trigger PR-title CI. Supply a PAT or GitHub App token when those checks are required.
