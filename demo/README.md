# Demo recording

`record-demo.mjs` plays the portal's 90-second demo in a real browser window, with a visible
cursor and captions, so every screen-recording take comes out the same. It uses
[Playwright](https://playwright.dev) and needs [Node.js](https://nodejs.org) 18 or newer.

## One-time setup

1. Run the portal as described in the main README (`php -S localhost:8000`), with
   `config/secrets.php` holding your database connection string and Gemini key.
2. Load the sample projects once: http://localhost:8000/src/model/import_json_projects.php
3. Have two accounts ready: a student who is a member of a project, and an admin (create the
   first admin with http://localhost:8000/init_admin.php).
4. Open that project in the browser and copy its id from the address bar
   (`Project_details.php?id=...`). Give it a few timeline milestones so the timeline looks real.
5. Install Playwright and its browser:
   ```bash
   cd demo
   npm install
   npx playwright install chromium
   ```
6. Copy the settings file and fill in the accounts, the project id and two research-paper PDFs:
   ```bash
   cp demo.env.example demo.env
   ```

## Record

1. Keep the portal running. In `demo/`, run:
   ```bash
   npm run demo
   ```
2. The script signs in off camera, then waits. Start your screen recording on the browser
   window (Mac: Cmd + Shift + 5; Windows: OBS), then press Enter in the terminal.
3. The demo plays for about 90–120 seconds and ends on a card with the repo link. Stop
   recording, then press Enter to close the browser.

If a step can't be done automatically, the browser pauses and the terminal says which step.
Do that step by hand, then press Resume (the play button) in the Playwright Inspector, and the
demo carries on. Trim that part out when you edit.

Each run creates a project and a meeting in your database. Set `CREATE_PROJECT=off` or
`CREATE_MEETING=off` in `demo.env` for retakes, and delete the test entries afterwards.
