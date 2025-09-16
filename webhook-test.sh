#!/bin/bash

# GitHub Webhook Testing Script
# Usage: ./webhook-test.sh [test-type]

BASE_URL="http://localhost:8000/api/v1"
WEBHOOK_URL="$BASE_URL/webhook/github"
DEBUG_URL="$BASE_URL/webhooks"

echo "🔗 GitHub Webhook Testing Suite"
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test 1: List available test commands
echo -e "\n${BLUE}📋 Available webhook test commands:${NC}"
echo "php artisan webhook:test --list"

# Test 2: Check webhook status
echo -e "\n${BLUE}📊 Checking webhook status...${NC}"
curl -s -X GET "$DEBUG_URL/status" \
  -H "Content-Type: application/json" \
  | jq '.' 2>/dev/null || echo "Status endpoint not accessible (authentication required)"

# Test 3: Test webhook processing (requires authentication)
echo -e "\n${YELLOW}⚠️  Webhook debugging endpoints require authentication${NC}"
echo "Use these endpoints after logging in:"
echo "  GET  $DEBUG_URL/status  - Check processing status"
echo "  GET  $DEBUG_URL/logs    - View recent webhook logs"
echo "  POST $DEBUG_URL/test    - Test webhook with custom payload"
echo "  DELETE $DEBUG_URL/cache - Clear webhook cache"

# Test 4: Show example test payload
echo -e "\n${BLUE}📝 Example test webhook payload:${NC}"
cat << 'EOF'
{
  "repository": {
    "id": 123456789,
    "name": "test-repo",
    "full_name": "owner/test-repo",
    "owner": {
      "login": "owner"
    }
  },
  "ref": "refs/heads/main",
  "commits": [
    {
      "id": "abc123",
      "message": "Test commit",
      "author": {
        "name": "Test User",
        "email": "test@example.com"
      },
      "added": ["new-file.txt"],
      "modified": ["existing-file.txt"]
    }
  ]
}
EOF

# Test 5: Show artisan commands
echo -e "\n${GREEN}🛠️  Available artisan commands:${NC}"
echo "php artisan webhook:test --list                    # List event types"
echo "php artisan webhook:test --event=push --repository=1  # Test specific event"
echo "php artisan webhook:test --ngrok                   # Test with ngrok"

# Test 6: Instructions for ngrok testing
echo -e "\n${YELLOW}🔗 ngrok Testing Instructions:${NC}"
echo "1. Start your Laravel app: php artisan serve"
echo "2. Start ngrok: ngrok http 8000"
echo "3. Run webhook test: php artisan webhook:test --ngrok"
echo "4. Or manually test webhook endpoint via ngrok URL"

echo -e "\n${GREEN}✅ Testing script completed!${NC}"
echo "Use the commands above to test your webhook implementation."