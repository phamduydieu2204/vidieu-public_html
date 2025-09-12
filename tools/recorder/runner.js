const { execSync } = require('child_process');
const https = require('https');
const { recordBuyNowVariable } = require('./record-buy-now-variable');

// Parse command line arguments
const args = process.argv.slice(2);
const baseUrl = args.find(arg => arg.startsWith('--base='))?.split('=')[1] || 'https://vidieu.vn';

// Get current HEAD commit SHA
function getCurrentSHA() {
  try {
    return execSync('git rev-parse HEAD', { encoding: 'utf8' }).trim();
  } catch (error) {
    console.error('Failed to get current SHA:', error.message);
    return null;
  }
}

// Fetch deploy marker from server
function fetchDeployMarker(url) {
  return new Promise((resolve, reject) => {
    https.get(url, (res) => {
      if (res.statusCode !== 200) {
        reject(new Error(`HTTP ${res.statusCode}`));
        return;
      }
      
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data.trim()));
    }).on('error', reject);
  });
}

// Wait for deployment to complete
async function waitForDeployment() {
  const currentSHA = getCurrentSHA();
  if (!currentSHA) {
    console.log('Cannot determine current SHA. Waiting 30 seconds as fallback...');
    await new Promise(resolve => setTimeout(resolve, 30000));
    return;
  }
  
  console.log(`Current commit SHA: ${currentSHA}`);
  console.log('Waiting for deployment to complete...');
  
  const deployMarkerUrl = `${baseUrl}/wp-content/deploy.txt`;
  const maxAttempts = 18; // 3 minutes (18 * 10 seconds)
  const pollInterval = 10000; // 10 seconds
  
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      console.log(`Checking deployment status (attempt ${attempt}/${maxAttempts})...`);
      const deployedSHA = await fetchDeployMarker(deployMarkerUrl);
      
      if (deployedSHA === currentSHA) {
        console.log('Deployment confirmed! SHA matches.');
        return;
      } else {
        console.log(`Deployed SHA: ${deployedSHA} (waiting for ${currentSHA})`);
      }
    } catch (error) {
      console.log(`Failed to fetch deploy marker: ${error.message}`);
    }
    
    if (attempt < maxAttempts) {
      await new Promise(resolve => setTimeout(resolve, pollInterval));
    }
  }
  
  console.log('Deploy marker check timed out. Falling back to 30 second wait...');
  await new Promise(resolve => setTimeout(resolve, 30000));
}

// Main runner
async function run() {
  console.log('=== Buy Now Variable Performance Recorder ===');
  console.log(`Base URL: ${baseUrl}`);
  console.log('');
  
  try {
    // Wait for deployment
    await waitForDeployment();
    
    // Run the recorder
    console.log('\nStarting performance recording...\n');
    await recordBuyNowVariable(baseUrl);
    
    console.log('\n✓ Recording completed successfully!');
  } catch (error) {
    console.error('\n✗ Recording failed:', error);
    process.exit(1);
  }
}

// Execute
run();