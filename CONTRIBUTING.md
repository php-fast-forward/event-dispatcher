# Contributing

Add one independent `.changelog/*.md` fragment for each observable change.
Use [Fast Forward Changelog](https://github.com/php-fast-forward/changelog)
through CPX with PHP 8.5 or newer; the package's PHP requirements stay unchanged.

```sh
cpx --skip-local fast-forward/changelog help add
cpx --skip-local fast-forward/changelog add "Describe the change." --category=fixed --no-interaction
cpx --skip-local fast-forward/changelog check --no-interaction
```

Choose the category and semantic impact that describe the change. Commit the
fragment with the contribution; leave published history intact and do not add
an `Unreleased` section to `CHANGELOG.md`. After committing, validate the PR delta
with `check --since=origin/main --source=tags --no-interaction` using the same CLI.
The tag source permits this local validation without GitHub credentials; the
workflow uses `source: github` to preserve published release notes.

[The installed project skill](.agents/skills/fast-forward-changelog/SKILL.md)
explains the contribution and release lifecycle. Check its integrity with
`cpx --skip-local fast-forward/changelog skill:install --check --json`.
The skill is a local instruction payload, independent of a Composer dependency.

The GitHub workflow checks fragments, maintains PR feedback and prepares a
version PR from stable ancestral tags and committed history. Review that PR's
version, history and fragment deletions before merging it. It never merges itself;
its approved merge publishes the version tag and GitHub Release. Installing the
workflow or merging an ordinary contribution does not publish a release.

There is no committed `CHANGELOG.md` yet. The first version PR imports the
existing GitHub Release text before adding the new notes. The sole historical
release, `v1.0.0`, currently contains only its **Full Changelog** link; that text
is preserved rather than replaced with invented summaries or PR references.
New fragment origins are resolved to their qualifying pull requests during
consolidation, without requiring a second edit after opening the contribution.
Publication uses the approved version's notes for the matching GitHub Release.
Version PR titles follow
`chore(release): fast-forward/event-dispatcher v{current_version} → v{next_version}`.
