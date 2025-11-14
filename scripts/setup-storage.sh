#!/bin/bash

# Script để setup storage symlink và permissions cho Laravel Task File Upload
# Chạy script này sau khi deploy hoặc khi gặp lỗi 403 FORBIDDEN khi truy cập files

echo "🔧 Setting up Laravel Storage for Task Files..."
echo ""

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT" || exit

# 1. Create storage symlink
echo "📎 Step 1: Creating storage symlink..."
if [ -L "public/storage" ]; then
    echo "   ✅ Symlink already exists: public/storage"
    ls -la public/storage
else
    php artisan storage:link
    if [ -L "public/storage" ]; then
        echo "   ✅ Symlink created successfully!"
        ls -la public/storage
    else
        echo "   ❌ Failed to create symlink!"
        exit 1
    fi
fi
echo ""

# 2. Set storage permissions
echo "🔐 Step 2: Setting storage permissions..."
chmod -R 775 storage/
chmod -R 775 public/storage 2>/dev/null || echo "   ⚠️  public/storage not found (may need symlink first)"
echo "   ✅ Permissions set to 775"
echo ""

# 3. Create task-files directory if not exists
echo "📁 Step 3: Creating task-files directory..."
mkdir -p storage/app/public/task-files
chmod -R 775 storage/app/public/task-files
echo "   ✅ Directory created: storage/app/public/task-files"
echo ""

# 4. Verify configuration
echo "✅ Step 4: Verifying configuration..."
if [ -f "config/filesystems.php" ]; then
    echo "   ✅ filesystems.php exists"
else
    echo "   ❌ filesystems.php not found!"
fi

if [ -d "storage/app/public" ]; then
    echo "   ✅ storage/app/public exists"
else
    echo "   ❌ storage/app/public not found!"
fi

if [ -L "public/storage" ]; then
    echo "   ✅ public/storage symlink exists"
    echo "   → Points to: $(readlink public/storage)"
else
    echo "   ❌ public/storage symlink NOT found!"
fi
echo ""

# 5. Test file access (if files exist)
echo "🧪 Step 5: Testing file access..."
if [ -d "storage/app/public/task-files" ] && [ "$(ls -A storage/app/public/task-files 2>/dev/null)" ]; then
    echo "   📂 Found files in task-files directory:"
    ls -lh storage/app/public/task-files/*/ 2>/dev/null | head -5 || echo "   (checking structure...)"
else
    echo "   ℹ️  No files found yet (upload a file to test)"
fi
echo ""

# 6. Final checklist
echo "📋 Setup Checklist:"
echo "   [$( [ -L "public/storage" ] && echo "✅" || echo "❌" )] Storage symlink exists"
echo "   [$( [ -d "storage/app/public/task-files" ] && echo "✅" || echo "❌" )] Task-files directory exists"
echo "   [$( [ -r "storage/app/public" ] && echo "✅" || echo "❌" )] Storage is readable"
echo "   [$( [ -w "storage/app/public" ] && echo "✅" || echo "❌" )] Storage is writable"
echo ""

echo "✨ Setup complete!"
echo ""
echo "📝 Next steps:"
echo "   1. Upload a file via API: POST /api/v1/admin-tasks/{id}/files"
echo "   2. Check file URL in response: response.data.files[0].file_url"
echo "   3. Test URL in browser: http://localhost:8082/storage/task-files/{id}/{filename}"
echo ""

