const { chromium } = require('playwright');
const fs = require('fs/promises');
const path = require('path');

async function recordBuyNowVariable(baseUrl = 'https://vidieu.vn') {
  // Check kill switch
  if (process.env.DISABLE_PERF_RECORDER === 'true') {
    console.log('Performance recorder is disabled via DISABLE_PERF_RECORDER');
    return;
  }

  const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
  const artifactsDir = path.join(__dirname, '../../perf/artifacts', timestamp);
  
  // Create artifacts directory
  await fs.mkdir(artifactsDir, { recursive: true });
  
  console.log(`Starting Buy Now Variable recorder...`);
  console.log(`Artifacts will be saved to: ${artifactsDir}`);
  
  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    recordHar: { path: path.join(artifactsDir, 'home-buy-now-variable.har') },
    recordVideo: { dir: artifactsDir }
  });
  
  const page = await context.newPage();
  
  // Collect console logs
  const consoleLogs = [];
  page.on('console', msg => {
    consoleLogs.push(`[${msg.type()}] ${msg.text()}`);
  });
  
  // Enable CDP for event listeners
  const cdpSession = await context.newCDPSession(page);
  await cdpSession.send('Runtime.enable');
  await cdpSession.send('DOMDebugger.enable');
  
  try {
    // Start tracing
    await context.tracing.start({ 
      screenshots: true, 
      snapshots: true,
      sources: true 
    });
    
    // Navigate to homepage
    console.log(`Navigating to ${baseUrl}...`);
    await page.goto(baseUrl, { waitUntil: 'networkidle' });
    
    // Find variable product with eye icon
    console.log('Looking for variable product...');
    const productCard = await page.locator('.product-item').filter({ 
      has: page.locator('.pe-7s-look') 
    }).first();
    
    if (!await productCard.isVisible()) {
      throw new Error('No variable product found on homepage');
    }
    
    // Click eye icon
    console.log('Clicking eye icon...');
    await productCard.locator('.pe-7s-look').click();
    
    // Wait for attribute selection panel
    await page.waitForSelector('.nasa-product-content-select-wrap', { timeout: 5000 });
    
    // Select attributes
    console.log('Selecting product attributes...');
    const attributeGroups = await page.locator('.nasa-product-content-child').all();
    
    for (const group of attributeGroups) {
      const enabledOption = await group.locator('.nasa-attr-ux-item.enabled').first();
      if (await enabledOption.isVisible()) {
        await enabledOption.click();
        await page.waitForTimeout(300); // Small delay between selections
      }
    }
    
    // Wait for Buy Now button to be enabled
    console.log('Waiting for Buy Now button to activate...');
    const buyNowButton = page.locator('.vd-buy-now-button.vd-buy-now-variable[data-variation-selected="true"]');
    await buyNowButton.waitFor({ state: 'visible', timeout: 5000 });
    
    // Capture event listeners before clicking
    console.log('Capturing event listeners...');
    
    // Get listeners for document
    const documentListeners = await cdpSession.send('DOMDebugger.getEventListeners', {
      objectId: (await cdpSession.send('Runtime.evaluate', {
        expression: 'document'
      })).result.objectId
    });
    await fs.writeFile(
      path.join(artifactsDir, 'home.event_listeners.document.json'),
      JSON.stringify(documentListeners, null, 2)
    );
    
    // Get listeners for body
    const bodyListeners = await cdpSession.send('DOMDebugger.getEventListeners', {
      objectId: (await cdpSession.send('Runtime.evaluate', {
        expression: 'document.body'
      })).result.objectId
    });
    await fs.writeFile(
      path.join(artifactsDir, 'home.event_listeners.body.json'),
      JSON.stringify(bodyListeners, null, 2)
    );
    
    // Get listeners for buy now button
    const buyNowElementHandle = await buyNowButton.elementHandle();
    const buyNowObjectId = (await cdpSession.send('DOM.requestNode', {
      objectId: (await buyNowElementHandle.evaluateHandle(el => el)).asElement()._remoteObject.objectId
    })).nodeId;
    
    const buyNowListeners = await cdpSession.send('DOMDebugger.getEventListeners', {
      objectId: (await cdpSession.send('Runtime.evaluate', {
        expression: `document.querySelector('.vd-buy-now-button.vd-buy-now-variable[data-variation-selected="true"]')`
      })).result.objectId
    });
    await fs.writeFile(
      path.join(artifactsDir, 'home.event_listeners.buy_now_btn.json'),
      JSON.stringify(buyNowListeners, null, 2)
    );
    
    // Click Buy Now and wait for navigation
    console.log('Clicking Buy Now button...');
    const clickTime = new Date().toISOString();
    
    const [navigation] = await Promise.all([
      page.waitForNavigation({ url: '**/checkout/**', timeout: 10000 }),
      buyNowButton.click()
    ]);
    
    const finalUrl = page.url();
    console.log(`Navigation complete. Final URL: ${finalUrl}`);
    
    // Stop tracing
    await context.tracing.stop({ path: path.join(artifactsDir, 'home-buy-now-variable.trace.zip') });
    
    // Save console logs
    await fs.writeFile(
      path.join(artifactsDir, 'home-buy-now-variable.console.txt'),
      consoleLogs.join('\n')
    );
    
    // Get HAR info
    await context.close();
    const harContent = await fs.readFile(path.join(artifactsDir, 'home-buy-now-variable.har'), 'utf-8');
    const har = JSON.parse(harContent);
    const totalRequests = har.log.entries.length;
    
    // Output summary
    console.log('\n=== Recording Complete ===');
    console.log(`Total requests: ${totalRequests}`);
    console.log(`Final URL: ${finalUrl}`);
    console.log(`Click time: ${clickTime}`);
    console.log(`Artifacts saved to: ${artifactsDir}`);
    console.log('\nArtifacts:');
    console.log(`- ${path.join(artifactsDir, 'home-buy-now-variable.har')}`);
    console.log(`- ${path.join(artifactsDir, 'home-buy-now-variable.console.txt')}`);
    console.log(`- ${path.join(artifactsDir, 'home-buy-now-variable.trace.zip')}`);
    console.log(`- ${path.join(artifactsDir, 'home.event_listeners.document.json')}`);
    console.log(`- ${path.join(artifactsDir, 'home.event_listeners.body.json')}`);
    console.log(`- ${path.join(artifactsDir, 'home.event_listeners.buy_now_btn.json')}`);
    
  } catch (error) {
    console.error('Recording failed:', error);
    throw error;
  } finally {
    await browser.close();
  }
}

// Run if called directly
if (require.main === module) {
  const baseUrl = process.argv.find(arg => arg.startsWith('--base='))?.split('=')[1] || 'https://vidieu.vn';
  recordBuyNowVariable(baseUrl).catch(console.error);
}

module.exports = { recordBuyNowVariable };