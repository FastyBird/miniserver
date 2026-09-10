# Security Policies and Procedures

This document outlines security procedures and general policies for the
**FastyBird IoT MiniServer** project.

- [Reporting a Vulnerability](#reporting-a-vulnerability)
- [Disclosure Policy](#disclosure-policy)
- [Known Issues](#known-issues)
- [Comments on this Policy](#comments-on-this-policy)

## Reporting a Vulnerability

Please **do not open a public issue** for security vulnerabilities.

Report them by email to **security@fastybird.com**, or privately through
[GitHub's security advisory form](https://github.com/FastyBird/miniserver/security/advisories/new).

- You will receive an acknowledgement within **48 hours**.
- Within **48 hours** you will receive a more detailed response outlining the next steps.
- We will keep you informed of progress towards a fix and may ask for additional
  information or clarification.

If the issue is in a third-party dependency, please also report it to that project's
maintainers.

## Disclosure Policy

When a report is received, a primary handler is assigned to coordinate the fix and
release process:

- Confirm the vulnerability and determine the affected versions.
- Audit the codebase for related issues.
- Develop and test a fix.
- Release it as quickly as possible.

## Known Issues

**The signing key committed to this repository's history is compromised.** Until
September 2026 this repository shipped a default JWT signing key, and it remains
readable in the git history of a public repository. It must be treated as public.

Current images do not use it: the container generates a unique key on first start and
refuses to boot if it cannot. **If you deployed a build from before that change, or set
`FB_APP_PARAMETER__SECURITY_SIGNATURE` to the old shipped default, rotate it.** Rotating
invalidates every token issued under the old key, which is the intended effect.

Secret scanning and push protection are enabled on this repository to prevent a repeat.

## Comments on this Policy

Suggestions for improving this policy are welcome — please open a pull request.
