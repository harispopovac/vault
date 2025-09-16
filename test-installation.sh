#!/bin/bash

# Demo Module Installation Test Script
# This script demonstrates how to install the demo module in another project

set -e  # Exit on any error

echo "🚀 Demo Module Installation Test"
echo "================================"

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: Please run this script from your Laravel project root"
    exit 1
fi

echo "📦 Step 1: Installing Demo Module Files via Git Subtree..."
echo "⚠️  IMPORTANT: This must be done FIRST before Composer can find the package!"
git subtree add --prefix=feature-modules/demo https://github.com/nermedin/template.git demo-module --squash

echo "🔧 Step 2: Configuring Composer Repository..."
# Backup original composer.json
cp composer.json composer.json.backup

# Use jq to add repository if available, otherwise show manual instructions
if command -v jq &> /dev/null; then
    echo "   Adding repository to composer.json with jq..."
    jq '.repositories += [{"type": "path", "url": "./feature-modules/demo/backend"}]' composer.json > composer.json.tmp && mv composer.json.tmp composer.json
else
    echo "   ⚠️  Please manually add this to your composer.json repositories section:"
    echo "   {"
    echo "     \"type\": \"path\","
    echo "     \"url\": \"./feature-modules/demo/backend\""
    echo "   }"
    echo ""
    echo "   Or install jq and run this script again."
    read -p "   Press Enter after adding the repository to composer.json..."
fi

echo "🎵 Step 3: Installing Backend Package..."
composer require demo/demo-module:@dev

echo "📦 Step 4: Configuring NPM Package..."
# Backup original package.json
cp package.json package.json.backup

# Use jq to add dependency if available
if command -v jq &> /dev/null; then
    echo "   Adding frontend package to package.json with jq..."
    jq '.dependencies["demo-module-frontend"] = "file:./feature-modules/demo/frontend"' package.json > package.json.tmp && mv package.json.tmp package.json
else
    echo "   ⚠️  Please manually add this to your package.json dependencies:"
    echo "   \"demo-module-frontend\": \"file:./feature-modules/demo/frontend\""
    echo ""
    read -p "   Press Enter after adding the dependency to package.json..."
fi

echo "📱 Step 5: Installing Frontend Package..."
npm install

echo "🔍 Step 6: Verifying Installation..."

# Check if module files exist
if [ -d "feature-modules/demo/backend" ] && [ -d "feature-modules/demo/frontend" ]; then
    echo "✅ Module files installed successfully"
else
    echo "❌ Module files not found"
    exit 1
fi

# Check if backend package is installed
if composer show demo/demo-module > /dev/null 2>&1; then
    echo "✅ Backend package installed successfully"
else
    echo "❌ Backend package installation failed"
    exit 1
fi

# Check if frontend package is installed
if npm list demo-module-frontend > /dev/null 2>&1; then
    echo "✅ Frontend package installed successfully"
else
    echo "❌ Frontend package installation failed"
    exit 1
fi

# Check if routes are available
echo "🛣️  Step 7: Checking Routes..."
if php artisan route:list | grep -q "demo"; then
    echo "✅ Demo module routes registered successfully"
else
    echo "⚠️  Demo module routes not found (may need to clear cache)"
    echo "   Try: php artisan package:discover"
fi

echo ""
echo "🎉 Installation Complete!"
echo "========================"
echo ""
echo "📋 Next Steps:"
echo "1. Create a demo page in your project:"
echo ""
echo "   resources/js/pages/demo.vue:"
echo "   ----------------------------------------"
echo "   <template>"
echo "     <VContainer>"
echo "       <DemoUsersTable />"
echo "     </VContainer>"
echo "   </template>"
echo ""
echo "   <script setup>"
echo "   import { DemoUsersTable } from 'demo-module-frontend'"
echo "   definePage({"
echo "     name: \"Demo\","
echo "     meta: { auth: true, layout: \"default\" }"
echo "   })"
echo "   </script>"
echo ""
echo "2. Add navigation item to resources/js/navigation/vertical/index.js:"
echo "   {"
echo "     title: \"Demo\","
echo "     to: { name: \"Demo\" },"
echo "     icon: { icon: \"tabler-wrecking-ball\" }"
echo "   }"
echo ""
echo "3. Register Vue components in your main.js:"
echo "   import { DemoUsersTable } from 'demo-module-frontend'"
echo ""
echo "4. Build assets: npm run dev"
echo ""
echo "5. Visit /demo in your application"
echo ""
echo "📖 For detailed instructions, see:"
echo "   feature-modules/demo/INSTALLATION.md"
echo ""
echo "🔧 If you encounter issues:"
echo "   - Check feature-modules/demo/ exists"
echo "   - Verify composer.json has the repository config"
echo "   - Run: php artisan package:discover"
echo "   - Clear caches: php artisan config:clear"
echo ""
echo "✨ Happy coding!" 
