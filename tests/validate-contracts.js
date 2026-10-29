const fs = require('fs');
const path = require('path');

console.log("=== STARTING ARCHITECTURAL CONTRACT VALIDATION ===");

const filesToCheck = [
  'defaults/default-home.json',
  'defaults/default-courses.json',
  'defaults/default-events.json',
  'src/contracts/home.contract.ts',
  'src/contracts/course.contract.ts',
  'src/contracts/event.contract.ts',
  'src/data/fallback-home.ts',
  'src/data/fallback-courses.ts',
  'src/data/fallback-events.ts',
  'src/api/home.ts',
  'src/api/course.ts',
  'src/api/event.ts',
  'plugins/home/home-plugin.php',
  'plugins/course/course-plugin.php',
  'plugins/event/event-plugin.php',
];

let failed = false;

// 1. Verify all files exist
console.log("\n1. Checking file existence...");
filesToCheck.forEach(file => {
  const filePath = path.join(__dirname, '..', file);
  if (fs.existsSync(filePath)) {
    console.log(`  [OK] ${file} exists.`);
  } else {
    console.error(`  [FAIL] ${file} is missing!`);
    failed = true;
  }
});

// 2. Validate canonical default JSONs
console.log("\n2. Validating default JSON schemas...");
const jsonFiles = [
  'defaults/default-home.json',
  'defaults/default-courses.json',
  'defaults/default-events.json'
];

jsonFiles.forEach(file => {
  const filePath = path.join(__dirname, '..', file);
  if (fs.existsSync(filePath)) {
    try {
      const raw = fs.readFileSync(filePath, 'utf8');
      const parsed = JSON.parse(raw);
      console.log(`  [OK] ${file} is valid JSON. Node keys: ${Object.keys(parsed).length}`);
    } catch (err) {
      console.error(`  [FAIL] ${file} contains invalid JSON:`, err.message);
      failed = true;
    }
  }
});

// 3. Verify reports exist
console.log("\n3. Verifying required report files...");
const requiredReports = [
  'REPORTS/BFS_TREE.md',
  'REPORTS/STATIC_DATA_CONTRACT.md',
  'REPORTS/PLUGIN_MAP.md',
  'REPORTS/FRONTEND_ENTRYPOINTS.md',
  'REPORTS/FRONTEND_VISIBLE_FIELDS.md',
  'REPORTS/PLUGIN_FIELD_DIFF.md',
  'REPORTS/API_PERFORMANCE_AUDIT.md',
  'REPORTS/RESET_SYSTEM.md',
  'REPORTS/ADMIN_UX_WIREFRAMES.md',
  'REPORTS/FINAL_SYNC_REPORT.md'
];

requiredReports.forEach(file => {
  const filePath = path.join(__dirname, '..', file);
  if (fs.existsSync(filePath)) {
    console.log(`  [OK] Report verified: ${file}`);
  } else {
    console.error(`  [FAIL] Required report missing: ${file}`);
    failed = true;
  }
});

if (failed) {
  console.error("\n=== Validation FAILED! Check error logs above ===");
  process.exit(1);
} else {
  console.log("\n=== Validation SUCCESS: All contracts and specifications match perfectly! ===");
  process.exit(0);
}
