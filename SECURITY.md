# Security Policy

## 1. Purpose and scope

This policy defines how **Kitodo.Publication** handles security vulnerabilities and incidents for the repository [kitodo/kitodo-publication](https://github.com/kitodo/kitodo-publication).

It applies to:

- GitHub repository: [kitodo/kitodo-publication](https://github.com/kitodo/kitodo-publication)
- All supported versions as listed in [SUPPORTED_VERSIONS.md](SUPPORTED_VERSIONS.md)

---

## 2. Roles and responsibilities

- **Security Contact / Maintainer**
    - Handles all vulnerability intake, triage, fixes, and releases.
    - GitHub: @Erikmitk

---

## 3. Secure development practices

- **Dependabot** alerts and automated security updates are enabled for all supported branches.
- **Secret scanning** is enabled.
- **Static analysis** (PHPStan) and **unit tests** run via GitHub Actions on every push and on pull requests targeting each supported branch.
- Third-party contributions must go through pull requests.

---

## 4. Vulnerability intake and handling

### 4.1 Reporting channels

Vulnerabilities can be reported via **GitHub private vulnerability reporting**: use the "Report a vulnerability" button in the Security tab of this repository.

### 4.2 Disclosure

Users are notified via GitHub release notes.

---

## 5. Security incident management

A security incident may involve:

- Compromise of the GitHub repository, GitHub Actions secrets, or package registry.
- Malicious code injection into a default branch, release, or workflow.

### 5.1 Detection

Monitor for:

- Unexpected pushes or workflow modifications
- Unexpected changes to repository or org settings

### 5.2 Initial response

- **Contain**: Temporarily restrict repository access if needed; rotate GitHub Secrets and any compromised credentials; disable suspicious GitHub Actions workflows.
- **Record**: Create an internal incident record capturing timeline, affected components, suspected cause, and current status.

---

## 6. Documentation and retention

Vulnerability and incident records are tracked via GitHub Security Advisories and GitHub Issues in this repository for as long as the repository exists.

---

## 7. Policy review

This policy is reviewed after major dependency changes or security incidents.
