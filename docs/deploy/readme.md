# Deployments

## Deployment Flag

When a deployment is in progress, a special "deployment flag" file is created on the server. This file is used to indicate that a deployment is currently running, and can be checked by your application or by administrators to prevent conflicting operations or to display maintenance messages.

### How it works

- **Flag file location:**
  The flag file is created at:
  `<DEPLOY_DST>/deploying`
  where `<DEPLOY_DST>` is your deployment destination directory on the server.

- **Creation:**
  The deployment workflow creates the flag file by running:
  ```
  touch <DEPLOY_DST>/deploying
  ```
  This happens before files are synchronized to the server.

- **Removal:**
  The flag file is automatically removed at the end of the deployment process, whether the deployment succeeds or fails.
