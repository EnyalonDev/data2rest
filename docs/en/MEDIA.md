# 🖼️ Media Library Module

[← Back to Main README](../../README.en.md)

## 📋 Description

The **Media Library Module** is a comprehensive solution for managing digital assets within Data2Rest. It allows not only organizing and viewing files but also performing advanced image editing, managing the recycle bin, and tracking file usage across all system databases.

---

## ✨ Key Features

### 📁 Organization and Visualization
- **Folder Navigation**: Structured organization by dates and tables.
- **Dual Views**: Toggle between **Grid** and **List** views for convenience.
- **Dynamic Breadcrumbs**: Fast navigation between directories with a compact path bar.
- **Real-Time Search**: Filter your files instantly by name.

### 🎨 Professional Image Editor
Powerful native integration for image manipulation without leaving the panel:
- **Crop**: Dimension adjustment with real-time preview.
- **Resize**: Width and height adjustment maintaining aspect ratio.
- **Artistic Filters**: Grayscale, Sepia, Invert, Vintage, Dramatic, Blur, and Sharpen.
- **Optimization**: Quality control (JPEG/WebP) to balance weight and sharpness.
- **Security**: **"Save as copy"** option enabled by default to protect originals.

### 🗑️ Recycle Bin and Retention
- **Secure Deletion**: Deleted files are moved to a `.trash` bin.
- **One-Click Restore**: Recover accidentally deleted files to their original location.
- **Automatic Purge**: Configure how many days files should remain in the trash before being permanently deleted.

### 📊 Usage Tracker
- **Orphan Detection**: Identification of files not being used in any table.
- **Reference Map**: Visualize exactly which database and table references each file before deleting it.

### 🛠️ Development and Maintenance Tools
- **Super Refresh**: Button to force interface reload ignoring browser cache.
- **Cache Cleaning**: Tool to purge temporary files and optimize the server.

---

## 🚀 Using the Image Editor

1. Select an **image** in the gallery.
2. In the right pane (Inspector), click the **Edit (Pencil)** button.
3. The editor modal will open with the following options:
   - **Transform**: Use the mouse to select the crop area.
   - **Filters**: Choose from over 8 artistic effects.
   - **Dimensions**: Manually change the size.
   - **Quality**: Adjust the optimization slider.
4. Click **Save Changes**. If "Save as copy" is checked, a new file with the suffix `-edited` will be created.

---

## 🔧 Technical Details

### File Location
```
public/uploads/
├── YYYY-MM-DD/     # Organization by date
├── .trash/         # Recycle bin
└── [tables]/       # Module specific files
```

### Main Controller
`src/Modules/Media/MediaController.php`

**Key Methods:**
- `list()`: Scanning and listing files with metadata.
- `edit()`: Image processing using PHP's **GD** library.
- `usage()`: Cross-search algorithm in multiple SQLite databases.
- `bulkDelete()`, `restore()`, `purge()`: File lifecycle management.

---

## 🔒 Security and Integrity

### 🔗 Robust Integration
- **External URL Support**: Intelligent detection of images in signed links or with query parameters (e.g., `image.jpg?token=123`).
- **Path Validation**: Security system preventing access to files outside current project scope (`../ traversal attack prevention`).
- **Granular Permissions**: Requires specific permissions (`module:media.view_files`) for access.

---

[← Back to Main README](../../README.en.md)
