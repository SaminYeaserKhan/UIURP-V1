// Plays the UIU Research Portal demo in a real browser window so you can screen-record
// smooth, repeatable takes. A visible cursor and captions are drawn onto the page as it runs.
// Setup and usage: demo/README.md
//
//   node record-demo.mjs     sign in, wait for Enter, then play the demo

import { chromium } from "playwright";
import { existsSync, readFileSync } from "node:fs";
import path from "node:path";
import readline from "node:readline/promises";
import { fileURLToPath } from "node:url";

const here = path.dirname(fileURLToPath(import.meta.url));
const cfg = loadConfig(path.join(here, "demo.env"));
const APP = (cfg.APP_URL || "http://localhost:8000").replace(/\/$/, "");
const [WIDTH, HEIGHT] = (cfg.WINDOW || "1440x810").split("x").map(Number);
const SPEED = Number(cfg.SPEED || 1); // 2 = twice as fast, 0.5 = half speed
const CAPTIONS = cfg.CAPTIONS !== "off";
const HEADLESS = cfg.HEADLESS === "on";

function loadConfig(file) {
  if (!existsSync(file)) {
    console.error(`Missing ${file}\nCopy demo.env.example to demo.env and fill it in.`);
    process.exit(1);
  }
  const values = {};
  for (const line of readFileSync(file, "utf8").split(/\r?\n/)) {
    const match = line.match(/^\s*([A-Z_]+)\s*=\s*(.*?)\s*$/);
    if (match) values[match[1]] = match[2];
  }
  return values;
}

function need(key) {
  if (!cfg[key]) {
    console.error(`Set ${key} in demo/demo.env first.`);
    process.exit(1);
  }
  return cfg[key];
}

function existingFile(value) {
  const file = path.resolve(here, value);
  if (!existsSync(file)) {
    console.error(`File not found: ${file}`);
    process.exit(1);
  }
  return file;
}

// ---------- cursor, captions and pacing ----------

const OVERLAY = `
  addEventListener("DOMContentLoaded", () => {
    const dot = document.createElement("div");
    dot.id = "demo-cursor";
    dot.style.cssText = "position:fixed;left:-50px;top:-50px;width:22px;height:22px;margin:-11px 0 0 -11px;" +
      "border-radius:50%;background:rgba(255,255,255,.9);border:2px solid rgba(20,20,20,.7);" +
      "box-shadow:0 2px 8px rgba(0,0,0,.35);pointer-events:none;z-index:2147483647;transition:transform .12s";
    document.body.appendChild(dot);
    addEventListener("mousemove", e => { dot.style.left = e.clientX + "px"; dot.style.top = e.clientY + "px"; }, true);
    addEventListener("mousedown", () => { dot.style.transform = "scale(.65)"; }, true);
    addEventListener("mouseup", () => { dot.style.transform = "scale(1)"; }, true);
  });
`;

let currentCaption = "";
let lastAlert = "";

const wait = ms => new Promise(resolve => setTimeout(resolve, ms / SPEED));

async function caption(page, text) {
  currentCaption = text;
  if (!CAPTIONS) return;
  await page.evaluate(value => {
    let bar = document.getElementById("demo-caption");
    if (!bar) {
      bar = document.createElement("div");
      bar.id = "demo-caption";
      bar.style.cssText = "position:fixed;left:50%;bottom:28px;transform:translateX(-50%);max-width:80vw;" +
        "padding:12px 22px;border-radius:12px;background:rgba(10,12,20,.82);color:#fff;" +
        "font:600 22px/1.35 system-ui,-apple-system,'Segoe UI',sans-serif;text-align:center;" +
        "pointer-events:none;z-index:2147483646;transition:opacity .25s";
      document.body.appendChild(bar);
    }
    bar.textContent = value;
    bar.style.opacity = value ? "1" : "0";
  }, text).catch(() => {});
}

async function go(page, route) {
  await page.goto(APP + route, { waitUntil: "domcontentloaded" });
  await page.waitForLoadState("networkidle", { timeout: 15000 }).catch(() => {});
  await wait(1500); // the pages fade their content in
  await caption(page, currentCaption);
}

async function pointAt(page, locator) {
  await locator.waitFor({ state: "visible", timeout: 15000 });
  await locator.scrollIntoViewIfNeeded();
  const box = await locator.boundingBox();
  if (!box) throw new Error("it isn't visible on the page");
  await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2, { steps: 25 });
  await wait(250);
}

async function click(page, locator) {
  await pointAt(page, locator);
  await locator.click();
  await wait(500);
}

async function type(page, locator, text) {
  await click(page, locator);
  await locator.fill("");
  await page.keyboard.type(text, { delay: 55 / SPEED });
  await wait(300);
}

async function scroll(page, pixels) {
  const steps = Math.max(1, Math.round(Math.abs(pixels) / 60));
  for (let i = 0; i < steps; i++) {
    await page.mouse.wheel(0, Math.sign(pixels) * 60);
    await wait(40);
  }
  await wait(400);
}

// Runs one part of the demo. If it fails, the browser pauses so you can do that part by
// hand, then press Resume in the Playwright Inspector to carry on.
async function step(page, name, action) {
  process.stdout.write(`  ${name} ... `);
  lastAlert = "";
  try {
    await action();
    console.log("done");
  } catch (error) {
    const reason = error.message.split("\n")[0] + (lastAlert ? ` (the app said: "${lastAlert}")` : "");
    console.log(`\n  ! Couldn't do this automatically: ${reason}`);
    if (HEADLESS) throw error;
    console.log("    Do it by hand in the browser, then press Resume (the play button) in the Playwright Inspector.\n");
    await page.pause();
  }
}

// ---------- the portal's flows ----------

async function signIn(page, username, password) {
  await go(page, "/login.php");
  const user = page.getByPlaceholder("Username or ID", { exact: true });
  if (!(await user.isVisible())) await click(page, page.locator('label[aria-label="Switch to login"]'));
  await type(page, user, username);
  await type(page, page.locator('input[name="Password"]'), password);
  await click(page, page.locator('form[action$="login_user.php"] button[type="submit"]'));
  await page.waitForURL(url => !/\/(login|login_user)\.php$/.test(url.pathname), { timeout: 20000 })
    .catch(() => { throw new Error(`couldn't sign in as ${username}; check the account in demo.env and that the database is reachable`); });
  await wait(800);
}

function tomorrow() {
  const day = new Date(Date.now() + 24 * 60 * 60 * 1000);
  return day.toISOString().slice(0, 10);
}

// ---------- run ----------

const projectId = need("PROJECT_ID");
const papers = need("PAPERS").split(",").map(item => existingFile(item.trim()));
const browser = await chromium.launch({
  headless: HEADLESS,
  args: [`--window-size=${WIDTH},${HEIGHT + 90}`],
});
const context = await browser.newContext({
  viewport: { width: WIDTH, height: HEIGHT },
  recordVideo: cfg.RECORD_VIDEO === "on" ? { dir: path.join(here, "videos"), size: { width: WIDTH, height: HEIGHT } } : undefined,
});
await context.addInitScript(OVERLAY);
const page = await context.newPage();
page.on("dialog", async dialog => {
  lastAlert = dialog.message();
  await dialog.accept();
});

console.log("Getting ready (off camera)...");
await step(page, "Sign in as the student", () => signIn(page, need("STUDENT_USERNAME"), need("STUDENT_PASSWORD")));
await go(page, "/index.php");
await caption(page, "");
if (!HEADLESS) {
  const prompt = readline.createInterface({ input: process.stdin, output: process.stdout });
  await prompt.question("\nStart your screen recording, then press Enter here to play the demo.");
  prompt.close();
}

await step(page, "Home page and search", async () => {
  await caption(page, "UIU Research Portal: find research projects and supervisors");
  await page.mouse.move(WIDTH / 2, HEIGHT / 2, { steps: 20 });
  await wait(2000);
  await type(page, page.locator("#search-bar"), cfg.SEARCH_TERM || "machine learning");
  await click(page, page.locator("#search-bttn"));
  await page.waitForURL(/Research_page\.php/, { timeout: 15000 });
  await wait(2500);
  await caption(page, "Search across titles, abstracts, fields and keywords");
  await scroll(page, 500);
  await wait(2000);
});

if (cfg.CREATE_PROJECT !== "off") {
  await step(page, "Create a project", async () => {
    await caption(page, "Create a research project with members, keywords and a cover image");
    await go(page, "/project_management.php");
    await click(page, page.locator("#new-project-tab"));
    await type(page, page.locator("#title"), cfg.NEW_PROJECT_TITLE || "Bangla Handwriting Recognition for Receipts");
    await type(page, page.locator("#abstract"), cfg.NEW_PROJECT_ABSTRACT ||
      "We build a dataset of handwritten Bangla receipts and train an OCR model to read them.");
    if (cfg.COVER_IMAGE) await page.locator("#coverImage").setInputFiles(existingFile(cfg.COVER_IMAGE));
    await type(page, page.locator("#field"), cfg.NEW_PROJECT_FIELD || "Computer Vision");
    for (const keyword of (cfg.NEW_PROJECT_KEYWORDS || "OCR, Bangla").split(",")) {
      await type(page, page.locator("#keyword"), keyword.trim());
      await click(page, page.locator("#addKeyword"));
    }
    await click(page, page.locator('#projectForm button[type="submit"]'));
    await wait(3000);
  });
}

await step(page, "Project timeline", async () => {
  await caption(page, "Each project has a timeline with milestones and assignments");
  await go(page, `/Project_details.php?id=${encodeURIComponent(projectId)}`);
  await pointAt(page, page.locator("#project-timeline"));
  await wait(2500);
});

if (cfg.CREATE_MEETING !== "off") {
  await step(page, "Schedule a meeting", async () => {
    await caption(page, "Schedule a meeting around every member's availability");
    await click(page, page.locator("#addMeetingBtn"));
    await type(page, page.locator("#meetingTitle"), cfg.MEETING_TITLE || "Weekly progress review");
    await type(page, page.locator("#meetingDescription"), "Review this week's results and plan next steps.");
    await type(page, page.locator("#meetingLink"), cfg.MEETING_LINK || "https://meet.google.com/abc-defg-hij");
    await page.locator("#meetingDate").fill(tomorrow());
    await page.locator("#meetingDate").dispatchEvent("change");
    await wait(2500);
    await click(page, page.locator("#timeSlots").locator("button, [onclick], .card, div[class*='time']").first());
    await wait(800);
    await click(page, page.locator("#saveMeetingBtn"));
    await wait(3000);
  });
}

await step(page, "Literature matrix", async () => {
  await caption(page, "Literature matrix: upload papers and Gemini summarises them side by side");
  await go(page, `/literature_matrix.php?id=${encodeURIComponent(projectId)}`);
  for (const tag of (cfg.MATRIX_TAGS || "Method, Dataset, Results").split(",")) {
    await type(page, page.locator("#newTagInput"), tag.trim());
    await click(page, page.getByRole("button", { name: "Add Tag", exact: true }));
  }
  await page.locator("#pdfUpload").setInputFiles(papers);
  await click(page, page.getByRole("button", { name: "Upload Files", exact: true }));
  await page.locator("#generateBtn").waitFor({ state: "visible", timeout: 60000 });
  await click(page, page.locator("#generateBtn"));
  await page.locator("table").first().waitFor({ state: "visible", timeout: 120000 });
  await wait(2000);
  await scroll(page, 500);
  await wait(3000);
});

await step(page, "Admin dashboard", async () => {
  await caption(page, "Signing in as an admin");
  await go(page, "/logout.php");
  await signIn(page, need("ADMIN_USERNAME"), need("ADMIN_PASSWORD"));
  await caption(page, "Admin dashboard: users, roles and content");
  await go(page, "/admin_dashboard.php");
  await wait(2000);
  for (const tab of ["#users", "#content", "#overview"]) {
    await click(page, page.locator(`[data-bs-target="${tab}"]`).first());
    await wait(2000);
  }
});

await step(page, "End card", async () => {
  await caption(page, "Team of four · 1st Runner-Up, UIU SE Lab Showcase  —  github.com/SaminYeaserKhan/UIURP-V1");
  await page.mouse.move(WIDTH - 5, HEIGHT - 5, { steps: 20 });
  await wait(5000);
});

console.log("\nDemo finished. Stop your screen recording.");
if (!HEADLESS) {
  const prompt = readline.createInterface({ input: process.stdin, output: process.stdout });
  await prompt.question("Press Enter to close the browser.");
  prompt.close();
}
await context.close();
await browser.close();
if (cfg.RECORD_VIDEO === "on") console.log(`Playwright's own recording is in ${path.join(here, "videos")}`);
