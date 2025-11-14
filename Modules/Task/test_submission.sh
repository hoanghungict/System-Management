#!/bin/bash

# 🧪 Test Submission Flow Script
# 
# Usage:
#   ./test_submission.sh <task_id> <token> [file_path]
#
# Examples:
#   ./test_submission.sh 119 "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
#   ./test_submission.sh 119 "token" test.pdf
#
# Note: Nếu không có file_path, script sẽ skip upload và dùng file_id = 1

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://localhost:8082"
TASK_ID=${1:-119}
TOKEN=${2:-""}
FILE_PATH=${3:-""}

# Check if token is provided
if [ -z "$TOKEN" ] || [ "$TOKEN" == "your_token_here" ]; then
  echo -e "${RED}❌ Error: JWT token is required${NC}"
  echo "Usage: ./test_submission.sh <task_id> <token> [file_path]"
  echo "Example: ./test_submission.sh 119 \"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...\" test.pdf"
  exit 1
fi

echo -e "${BLUE}🧪 Testing Submission Flow${NC}"
echo "=========================================="
echo -e "Task ID: ${YELLOW}$TASK_ID${NC}"
echo -e "Base URL: ${YELLOW}$BASE_URL${NC}"
echo ""

FILE_ID=""

# Step 1: Upload file (optional)
if [ -n "$FILE_PATH" ] && [ -f "$FILE_PATH" ]; then
  echo -e "${BLUE}📤 Step 1: Upload File${NC}"
  echo "----------------------"
  echo "Uploading: $FILE_PATH"
  
  UPLOAD_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST "$BASE_URL/api/v1/student-tasks/$TASK_ID/upload-file" \
    -H "Authorization: Bearer $TOKEN" \
    -F "file=@$FILE_PATH" 2>&1)
  
  HTTP_CODE=$(echo "$UPLOAD_RESPONSE" | grep "HTTP_CODE" | cut -d':' -f2)
  RESPONSE_BODY=$(echo "$UPLOAD_RESPONSE" | sed '/HTTP_CODE/d')
  
  echo "HTTP Status: $HTTP_CODE"
  echo "Response: $RESPONSE_BODY"
  echo ""
  
  if [ "$HTTP_CODE" -eq 200 ]; then
    # Try multiple methods to extract file ID
    # Method 1: Extract from "id" field in data object
    FILE_ID=$(echo "$RESPONSE_BODY" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    
    # Method 2: Use jq if available (more reliable)
    if [ -z "$FILE_ID" ] && command -v jq &> /dev/null; then
      FILE_ID=$(echo "$RESPONSE_BODY" | jq -r '.data.id // empty' 2>/dev/null)
    fi
    
    # Method 3: Extract from data.id pattern
    if [ -z "$FILE_ID" ]; then
      FILE_ID=$(echo "$RESPONSE_BODY" | grep -o '"data"[^}]*"id":[0-9]*' | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    fi
    
    if [ -n "$FILE_ID" ] && [ "$FILE_ID" != "null" ] && [ "$FILE_ID" != "" ]; then
      echo -e "${GREEN}✅ File uploaded successfully. File ID: $FILE_ID${NC}"
    else
      echo -e "${YELLOW}⚠️  Warning: Could not extract file ID from response${NC}"
      echo "Response body:"
      echo "$RESPONSE_BODY" | head -20
      echo ""
      read -p "Enter file ID (or press Enter to skip submit step): " FILE_ID
    fi
  else
    echo -e "${RED}❌ Failed to upload file (HTTP $HTTP_CODE)${NC}"
    read -p "Enter existing file ID to use (or press Enter to skip submit step): " FILE_ID
  fi
else
  echo -e "${YELLOW}⚠️  Step 1: Skip Upload (no file provided)${NC}"
  echo "Using existing file ID or you can provide one..."
  read -p "Enter file ID to use (or press Enter to use file_id=1): " FILE_ID
  FILE_ID=${FILE_ID:-1}
  echo -e "Using file ID: ${YELLOW}$FILE_ID${NC}"
fi

echo ""

# Step 2: Submit task
if [ -n "$FILE_ID" ] && [ "$FILE_ID" != "" ]; then
  echo -e "${BLUE}📝 Step 2: Submit Task with File ID: $FILE_ID${NC}"
  echo "---------------------------------------------"
  
  SUBMIT_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST "$BASE_URL/api/v1/student-tasks/$TASK_ID/submit" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d "{
      \"content\": \"Test submission created at $(date '+%Y-%m-%d %H:%M:%S')\",
      \"files\": [$FILE_ID]
    }" 2>&1)
  
  HTTP_CODE=$(echo "$SUBMIT_RESPONSE" | grep "HTTP_CODE" | cut -d':' -f2)
  RESPONSE_BODY=$(echo "$SUBMIT_RESPONSE" | sed '/HTTP_CODE/d')
  
  echo "HTTP Status: $HTTP_CODE"
  echo "Response: $RESPONSE_BODY"
  echo ""
  
  if [ "$HTTP_CODE" -eq 200 ] || [ "$HTTP_CODE" -eq 201 ]; then
    SUCCESS=$(echo "$RESPONSE_BODY" | grep -o '"success":true')
    if [ -n "$SUCCESS" ]; then
      echo -e "${GREEN}✅ Task submitted successfully${NC}"
    else
      echo -e "${YELLOW}⚠️  Warning: Response may indicate failure${NC}"
      echo "Check response above"
    fi
  else
    echo -e "${RED}❌ Failed to submit task (HTTP $HTTP_CODE)${NC}"
    echo "Stopping test..."
    exit 1
  fi
else
  echo -e "${YELLOW}⚠️  Step 2: Skip Submit (no file ID)${NC}"
fi

echo ""

# Step 3: Get submission
echo -e "${BLUE}📥 Step 3: Get Submission${NC}"
echo "-------------------------"

GET_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X GET "$BASE_URL/api/v1/student-tasks/$TASK_ID/submission" \
  -H "Authorization: Bearer $TOKEN" 2>&1)

HTTP_CODE=$(echo "$GET_RESPONSE" | grep "HTTP_CODE" | cut -d':' -f2)
RESPONSE_BODY=$(echo "$GET_RESPONSE" | sed '/HTTP_CODE/d')

echo "HTTP Status: $HTTP_CODE"
echo "Response: $RESPONSE_BODY"
echo ""

if [ "$HTTP_CODE" -eq 200 ]; then
  # Try to count files using different methods
  FILES_COUNT=$(echo "$RESPONSE_BODY" | grep -o '"files":\[[^]]*\]' | grep -o 'id' | wc -l || echo "0")
  
  # Alternative: check if files array has content
  HAS_FILES=$(echo "$RESPONSE_BODY" | grep -o '"files":\[[^]]*\]' | grep -v '"files":\[\]' || echo "")
  
  if [ -n "$HAS_FILES" ] && [ "$FILES_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✅ Files found in submission: $FILES_COUNT file(s)${NC}"
    
    # Extract and display file info
    echo ""
    echo "File details:"
    echo "$RESPONSE_BODY" | grep -o '"files":\[[^]]*\]' | grep -o '"id":[0-9]*' | sed 's/"id":/  - File ID: /'
  else
    echo -e "${RED}❌ No files found in submission${NC}"
    echo ""
    echo -e "${YELLOW}🔍 Debug Info:${NC}"
    echo "1. Check logs:"
    echo "   tail -f storage/logs/laravel.log | grep -E '(Submitting task|Loading submission files)'"
    echo ""
    echo "2. Check database:"
    echo "   SELECT id, task_id, student_id, submission_files, submitted_at"
    echo "   FROM task_submissions"
    echo "   WHERE task_id=$TASK_ID"
    echo "   ORDER BY id DESC LIMIT 1;"
    echo ""
    echo "3. Check if file exists:"
    echo "   SELECT id, task_id, name FROM task_file WHERE id=$FILE_ID AND task_id=$TASK_ID;"
  fi
elif [ "$HTTP_CODE" -eq 404 ]; then
  echo -e "${YELLOW}⚠️  No submission found (404)${NC}"
  echo "This is expected if submission hasn't been created yet."
else
  echo -e "${RED}❌ Failed to get submission (HTTP $HTTP_CODE)${NC}"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}✅ Test completed!${NC}"
echo ""
echo -e "${BLUE}💡 Tips:${NC}"
echo "- Check logs for detailed debugging info"
echo "- Verify submission_files in database"
echo "- Ensure file_id matches task_id"

