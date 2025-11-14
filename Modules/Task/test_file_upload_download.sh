#!/bin/bash

# Test File Upload & Download Flow for Student Task Submissions
# Usage: ./test_file_upload_download.sh

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://localhost:8082/api/v1"
TOKEN=""
TASK_ID=""
STUDENT_ID=""

# Function to print colored output
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_header() {
    echo -e "\n${BLUE}================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================${NC}\n"
}

# Check dependencies
check_dependencies() {
    print_header "Checking Dependencies"
    
    if ! command -v curl &> /dev/null; then
        print_error "curl is not installed"
        exit 1
    fi
    print_success "curl is installed"
    
    if ! command -v jq &> /dev/null; then
        print_warning "jq is not installed (optional, but recommended)"
    else
        print_success "jq is installed"
    fi
}

# Get token (Login first)
get_token() {
    print_header "Step 0: Login"
    
    read -p "Enter student email: " email
    read -s -p "Enter student password: " password
    echo ""
    
    response=$(curl -s -X POST "${BASE_URL}/auth/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"${email}\",\"password\":\"${password}\",\"user_type\":\"student\"}")
    
    TOKEN=$(echo "$response" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
    
    if [ -z "$TOKEN" ]; then
        print_error "Failed to login"
        echo "$response"
        exit 1
    fi
    
    print_success "Logged in successfully"
    print_info "Token: ${TOKEN:0:20}..."
}

# Get task list
get_task_list() {
    print_header "Step 1: Get Task List"
    
    response=$(curl -s -X GET "${BASE_URL}/lecturer-tasks" \
        -H "Authorization: Bearer ${TOKEN}")
    
    echo "$response" | jq '.' || echo "$response"
    
    read -p "Enter task ID to test: " TASK_ID
    
    if [ -z "$TASK_ID" ]; then
        print_error "Task ID is required"
        exit 1
    fi
    
    print_success "Task ID: ${TASK_ID}"
}

# Create test file
create_test_file() {
    print_header "Step 2: Create Test File"
    
    TEST_FILE="test_upload_$(date +%s).txt"
    echo "This is a test file for upload testing" > "$TEST_FILE"
    echo "Created at: $(date)" >> "$TEST_FILE"
    echo "Task ID: ${TASK_ID}" >> "$TEST_FILE"
    
    print_success "Created test file: ${TEST_FILE}"
}

# Upload file
upload_file() {
    print_header "Step 3: Upload File"
    
    print_info "Uploading ${TEST_FILE}..."
    
    response=$(curl -s -X POST "${BASE_URL}/lecturer-tasks/${TASK_ID}/upload-file" \
        -H "Authorization: Bearer ${TOKEN}" \
        -F "file=@${TEST_FILE}")
    
    echo "$response" | jq '.' || echo "$response"
    
    FILE_ID=$(echo "$response" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    
    if [ -z "$FILE_ID" ]; then
        print_error "Failed to upload file"
        exit 1
    fi
    
    print_success "File uploaded successfully"
    print_info "File ID: ${FILE_ID}"
}

# Submit task
submit_task() {
    print_header "Step 4: Submit Task with File"
    
    print_info "Submitting task ${TASK_ID} with file ${FILE_ID}..."
    
    response=$(curl -s -X POST "${BASE_URL}/lecturer-tasks/${TASK_ID}/submit" \
        -H "Authorization: Bearer ${TOKEN}" \
        -H "Content-Type: application/json" \
        -d "{
            \"content\": \"Test submission with file upload\",
            \"files\": [${FILE_ID}],
            \"notes\": \"This is a test submission created by automated script\"
        }")
    
    echo "$response" | jq '.' || echo "$response"
    
    success=$(echo "$response" | grep -o '"success":[^,]*' | cut -d':' -f2)
    
    if [ "$success" != "true" ]; then
        print_error "Failed to submit task"
        exit 1
    fi
    
    print_success "Task submitted successfully"
}

# Get submission
get_submission() {
    print_header "Step 5: Get Submission"
    
    print_info "Getting submission for task ${TASK_ID}..."
    
    response=$(curl -s -X GET "${BASE_URL}/lecturer-tasks/${TASK_ID}/submission" \
        -H "Authorization: Bearer ${TOKEN}")
    
    echo "$response" | jq '.' || echo "$response"
    
    # Check if files are in response
    files_count=$(echo "$response" | grep -o '"files":\[[^\]]*\]' | wc -l)
    
    if [ "$files_count" -eq 0 ]; then
        print_error "No files found in submission!"
        exit 1
    fi
    
    print_success "Submission retrieved successfully with files"
}

# Download file
download_file() {
    print_header "Step 6: Download File"
    
    print_info "Downloading file ${FILE_ID}..."
    
    DOWNLOAD_FILE="downloaded_${TEST_FILE}"
    
    http_code=$(curl -s -o "$DOWNLOAD_FILE" -w "%{http_code}" \
        -X GET "${BASE_URL}/lecturer-tasks/${TASK_ID}/files/${FILE_ID}/download" \
        -H "Authorization: Bearer ${TOKEN}")
    
    if [ "$http_code" -eq 200 ]; then
        print_success "File downloaded successfully: ${DOWNLOAD_FILE}"
        
        # Check if file exists and has content
        if [ -s "$DOWNLOAD_FILE" ]; then
            print_info "File size: $(wc -c < "$DOWNLOAD_FILE") bytes"
            print_info "File content:"
            echo "---"
            cat "$DOWNLOAD_FILE"
            echo "---"
        else
            print_error "Downloaded file is empty"
            exit 1
        fi
    else
        print_error "Failed to download file (HTTP ${http_code})"
        exit 1
    fi
}

# Cleanup
cleanup() {
    print_header "Step 7: Cleanup"
    
    if [ -f "$TEST_FILE" ]; then
        rm "$TEST_FILE"
        print_success "Removed test file: ${TEST_FILE}"
    fi
    
    if [ -f "$DOWNLOAD_FILE" ]; then
        rm "$DOWNLOAD_FILE"
        print_success "Removed downloaded file: ${DOWNLOAD_FILE}"
    fi
}

# Main test flow
main() {
    echo -e "${GREEN}"
    echo "╔══════════════════════════════════════════════════════════╗"
    echo "║   File Upload & Download Test for Student Submissions   ║"
    echo "╚══════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    
    check_dependencies
    get_token
    get_task_list
    create_test_file
    upload_file
    submit_task
    get_submission
    download_file
    cleanup
    
    print_header "✅ ALL TESTS PASSED!"
    
    echo -e "${GREEN}"
    echo "╔══════════════════════════════════════════════════════════╗"
    echo "║                    TEST SUMMARY                          ║"
    echo "╠══════════════════════════════════════════════════════════╣"
    echo "║  ✅ File Upload                                          ║"
    echo "║  ✅ Task Submit with File ID                             ║"
    echo "║  ✅ Get Submission with Files                            ║"
    echo "║  ✅ File Download                                        ║"
    echo "╚══════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

# Error handler
handle_error() {
    print_error "Test failed at line $1"
    cleanup
    exit 1
}

trap 'handle_error $LINENO' ERR

# Run tests
main

