@
const puppeteer = require('puppeteer');
(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
  console.log('Navigating to login...');
  await page.goto('http://127.0.0.1/login.php');
  await page.type('#username', 'admin');
  await page.type('#password', 'iisc_admin_2026');
  await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);
  console.log('Navigating to image_detail...');
  await page.goto('http://127.0.0.1/image_detail.php?image=cam1_20260326_110317_69c5127528aa0.jpg&device=device1');
  await new Promise(r => setTimeout(r, 1000));
  console.log('Clicking button...');
  await page.click('#rerunBtn');
  await new Promise(r => setTimeout(r, 2000));
  const html = await page.content();
  console.log(html.includes('Analyzing') ? 'Button worked' : 'Button did NOT work');
  await browser.close();
})();
@
