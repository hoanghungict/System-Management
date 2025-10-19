# 📁 Rules Structure Overview

## File Organization

```
Project Root/
├── .cursorrules                    # 🎯 Main entry point (quick reference)
├── .cursorignore                   # 🚫 Files to ignore during indexing
└── .cursor/
    └── rules/                      # 📚 Detailed specialized rules
        ├── README.md              # 📖 Complete rules overview
        ├── backend.md             # 🔧 Core backend rules
        ├── architecture.md        # 🏗️ Clean Architecture patterns
        ├── security.md            # 🔐 Security best practices
        ├── performance.md         # ⚡ Performance optimization
        ├── testing.md             # 🧪 Testing standards
        ├── documentation.md       # 📝 Documentation guidelines
        ├── index.md               # 📋 Rules directory index
        └── STRUCTURE.md           # 📁 This file
```

## How It Works

### 1. **`.cursorrules`** (Main Entry Point)
- Quick reference and overview
- Critical rules that must never be broken
- Project context and module-specific quick reference
- Commands and implementation guidelines
- References to detailed rules in `.cursor/rules/`

### 2. **`.cursor/rules/`** (Detailed Rules)
- **`README.md`**: Complete rules with examples and code snippets
- **`backend.md`**: Core backend development standards
- **`architecture.md`**: Clean Architecture implementation patterns
- **`security.md`**: Security best practices and implementation
- **`performance.md`**: Performance optimization strategies
- **`testing.md`**: Testing standards and patterns
- **`documentation.md`**: Documentation standards and examples
- **`index.md`**: Rules directory navigation
- **`STRUCTURE.md`**: This file explaining the structure

## Usage Flow

1. **Start with `.cursorrules`** for quick overview and critical rules
2. **Dive into `.cursor/rules/README.md`** for complete detailed rules
3. **Use specialized files** for specific topics:
   - Architecture decisions → `architecture.md`
   - Security implementation → `security.md`
   - Performance optimization → `performance.md`
   - Testing → `testing.md`
   - Documentation → `documentation.md`

## Benefits

✅ **Modular Organization**: Each topic has its own file
✅ **Quick Access**: `.cursorrules` provides immediate reference
✅ **Detailed Examples**: Specialized files contain code examples
✅ **Easy Maintenance**: Update specific topics without affecting others
✅ **Clear Navigation**: `index.md` helps navigate the structure
✅ **No Duplication**: Main rules in one place, details in specialized files

## Maintenance

- **Update `.cursorrules`** for quick reference changes
- **Update specialized files** for detailed rule changes
- **Keep `index.md`** updated when adding new rule files
- **Use `.cursorignore`** to prevent Cursor from re-indexing rules

## Adding New Rules

1. Create new file in `.cursor/rules/`
2. Update `index.md` to include the new file
3. Add reference in `.cursorrules` if it's critical
4. Update this `STRUCTURE.md` if needed
