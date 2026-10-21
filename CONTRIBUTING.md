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

Run `composer install` to fetch the project skill into
`.agents/skills/fast-forward-changelog`. The committed `skills.json` selects its
GitHub source through `llm/skills`; generated `.agents` files stay out of Git.
This does not install Changelog's runtime dependencies into the library.
Inspect the copy with `composer skills:show` and refresh compatible versions with
`composer skills:update --from=github`. Keep the skill under this manager's
ownership; do not run the legacy Changelog skill installer on it.

The GitHub workflow checks fragments, maintains PR feedback and prepares a
version PR from stable ancestral tags and committed history. Review that PR's
version, history and fragment deletions before merging it. It never merges itself;
its approved merge publishes the version tag and GitHub Release. Installing the
workflow or merging an ordinary contribution does not publish a release.

`CHANGELOG.md` was backfilled from the existing GitHub Release. The sole
historical release, `v1.0.0`, contains only its **Full Changelog** link; that text
is preserved rather than replaced with invented summaries or PR references.
Version headings link to the corresponding published release through footer
definitions. Historical maintenance preserves the original release text and
does not consume pending fragments.
New fragment origins are resolved to their qualifying pull requests during
consolidation, without requiring a second edit after opening the contribution.
Publication uses the approved version's notes for the matching GitHub Release.
Version PR titles follow
`chore(release): fast-forward/event-dispatcher v{current_version} → v{next_version}`.

## Composer dependency skills

The development-only manager restores `fast-forward-container` and
`fast-forward-config` from the installed Composer packages, including Config as
a transitive dependency. `skills.json` explicitly trusts only those two Composer
donors; dependency trust does not extend to other installed packages. No separate
GitHub source or version pin is used for either library.

Inspect donors with `composer skills:show` and refresh their payloads with
`composer skills:update --from=composer`. The existing GitHub source continues
to manage the Changelog contributor skill independently. Generated copies remain
ignored. Synchronization does not upgrade runtime dependencies; an installed
version without a bundled skill contributes no skill. Check installed versions
and the bundled skill's supported contract before using its instructions.
