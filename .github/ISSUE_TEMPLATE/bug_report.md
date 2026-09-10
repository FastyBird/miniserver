---
name: 🐛 Bug Report
about: Report a problem with FastyBird IoT MiniServer
title: "[Bug]: "
labels: "bug"
assignees: ""
---

## 🐞 Bug Description

A clear and concise description of what the bug is.

## 🔄 Steps to Reproduce

1. Go to '...'
2. Run '...'
3. See error

## 🤔 Expected Behavior

What you expected to happen instead.

## 🖥️ Environment

- **MiniServer version / image tag**: `X.X.X`
- **Deployment**: `[Docker compose, single container, other]`
- **Host OS**: `[Debian, Raspberry Pi OS, macOS, ...]`
- **Database**: `[MariaDB 10.11, ...]`
- **Affected extension**: `[module/devices, connector/shelly, ... or unknown]`

## 📋 Logs

Please include the relevant output. Inside the container:

```bash
docker logs <container>
docker exec <container> sh -c 'cat /data/logs/exception.log'
```

<details>
<summary>Logs</summary>

```
paste here
```

</details>

## 📎 Additional Context

Anything else that might help — screenshots, configuration (with secrets removed),
or when it started happening.

> ⚠️ Never paste your `FB_APP_PARAMETER__SECURITY_SIGNATURE`, database password or any
> other credential into an issue.
