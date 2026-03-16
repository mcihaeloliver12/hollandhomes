## GitHub auth (safe workflow)

### Recommended (PAT)
1) Create a Personal Access Token (classic) with **repo** scope.
2) In terminal:
   - `export GITHUB_TOKEN="PASTE_TOKEN_HERE"`
   - `echo "$GITHUB_TOKEN" | gh auth login --hostname github.com --with-token`
3) Push:
   - `git push -u origin main`

### Alternative (browser/device login)
1) Run:
   - `gh auth login -h github.com -p https -w`
2) Follow the browser/device prompt shown by the CLI.
3) Push:
   - `git push -u origin main`

### Notes
- Do not store tokens in files.
- If you need to re-auth: `gh auth logout -h github.com`
