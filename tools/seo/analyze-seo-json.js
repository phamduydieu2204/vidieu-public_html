#!/usr/bin/env node

/**
 * Analyze Lighthouse SEO JSON files
 */

const fs = require('fs');
const path = require('path');

const inputDir = path.join(__dirname, '../../wp-content/prerf/inputs');
const outputDir = path.join(__dirname, '../../wp-content/perf/seo');

// Priority mapping
const priorityMap = {
    'crawlable-anchors': 'P0',
    'link-text': 'P0',
    'meta-description': 'P0',
    'tap-targets': 'P1',
    'image-alt': 'P1',
    'robots-txt': 'P1',
    'hreflang': 'P2',
    'canonical': 'P2',
    'structured-data': 'P2'
};

// Ensure output directory exists
if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
}

console.log('Lighthouse SEO Analysis');
console.log('=======================\n');

const results = [];
const issuesByAudit = {};

// Get all JSON files
const jsonFiles = fs.readdirSync(inputDir).filter(f => f.endsWith('.json'));

jsonFiles.forEach(file => {
    const filePath = path.join(inputDir, file);
    const data = JSON.parse(fs.readFileSync(filePath, 'utf8'));
    
    if (!data.categories || !data.categories.seo) {
        console.log(`Skipping ${file}: No SEO data`);
        return;
    }
    
    const seo = data.categories.seo;
    const url = data.finalUrl || data.requestedUrl || 'Unknown';
    const score = Math.round(seo.score * 100);
    
    const pageResult = {
        file: file.replace('.json', ''),
        url: url,
        score: score,
        issues: [],
        p0Count: 0,
        p1Count: 0,
        p2Count: 0
    };
    
    // Analyze failed audits
    seo.auditRefs.forEach(auditRef => {
        if (auditRef.weight === 0) return;
        
        const audit = data.audits[auditRef.id];
        if (!audit || audit.score >= 1) return;
        
        const priority = priorityMap[auditRef.id] || 'P2';
        const issue = {
            id: auditRef.id,
            title: audit.title,
            description: audit.description,
            score: audit.score,
            priority: priority,
            examples: []
        };
        
        // Count by priority
        if (priority === 'P0') pageResult.p0Count++;
        else if (priority === 'P1') pageResult.p1Count++;
        else pageResult.p2Count++;
        
        // Extract examples
        if (audit.details && audit.details.items) {
            audit.details.items.slice(0, 3).forEach(item => {
                if (item.node) {
                    issue.examples.push(item.node.snippet || item.node.selector);
                }
            });
        }
        
        pageResult.issues.push(issue);
        
        // Track cross-page issues
        if (!issuesByAudit[auditRef.id]) {
            issuesByAudit[auditRef.id] = {
                title: audit.title,
                priority: priority,
                affectedPages: []
            };
        }
        issuesByAudit[auditRef.id].affectedPages.push(pageResult.file);
    });
    
    results.push(pageResult);
});

// Generate report
let report = '# Lighthouse SEO Analysis\n';
report += `**Generated**: ${new Date().toISOString()}\n`;
report += '**Target**: SEO Score ≥ 95\n\n';

// Summary table
report += '## Summary\n\n';
report += '| Page | Score | P0 | P1 | P2 | Status |\n';
report += '|------|-------|----|----|----|---------|\n';

results.forEach(r => {
    const status = r.score >= 95 ? '✅' : '❌';
    report += `| ${r.file} | ${r.score}/100 | ${r.p0Count} | ${r.p1Count} | ${r.p2Count} | ${status} |\n`;
});

// Print to console
console.log('Summary:');
results.forEach(r => {
    console.log(`${r.file}: ${r.score}/100 ${r.score >= 95 ? '✅' : '❌'}`);
    if (r.score < 95) {
        console.log(`  P0: ${r.p0Count}, P1: ${r.p1Count}, P2: ${r.p2Count}`);
    }
});

// Cross-page issues
report += '\n## Common Issues\n\n';

const p0Issues = Object.entries(issuesByAudit).filter(([, v]) => v.priority === 'P0');
const p1Issues = Object.entries(issuesByAudit).filter(([, v]) => v.priority === 'P1');

if (p0Issues.length > 0) {
    report += '### P0 - Critical Issues\n\n';
    console.log('\nP0 Issues:');
    p0Issues.forEach(([id, info]) => {
        report += `- **${info.title}** (\`${id}\`)\n`;
        report += `  Affects: ${info.affectedPages.join(', ')}\n\n`;
        console.log(`- ${id}: ${info.affectedPages.length} pages affected`);
    });
}

if (p1Issues.length > 0) {
    report += '### P1 - Important Issues\n\n';
    console.log('\nP1 Issues:');
    p1Issues.forEach(([id, info]) => {
        report += `- **${info.title}** (\`${id}\`)\n`;
        report += `  Affects: ${info.affectedPages.join(', ')}\n\n`;
        console.log(`- ${id}: ${info.affectedPages.length} pages affected`);
    });
}

// Detailed issues
report += '\n## Detailed Issues by Page\n\n';

results.forEach(r => {
    if (r.issues.length === 0) return;
    
    report += `### ${r.file}\n`;
    report += `**URL**: ${r.url}\n`;
    report += `**Score**: ${r.score}/100\n\n`;
    
    r.issues.forEach(issue => {
        report += `- **[${issue.priority}] ${issue.title}**\n`;
        if (issue.examples.length > 0) {
            report += '  Examples:\n';
            issue.examples.forEach(ex => {
                report += `  - \`${ex.substring(0, 80)}...\`\n`;
            });
        }
        report += '\n';
    });
});

// Write report
const outputFile = path.join(outputDir, `seo-analysis-${new Date().toISOString().split('T')[0]}.md`);
fs.writeFileSync(outputFile, report);
fs.writeFileSync(path.join(outputDir, 'seo-analysis-latest.md'), report);

console.log(`\nReport written to: ${outputFile}`);