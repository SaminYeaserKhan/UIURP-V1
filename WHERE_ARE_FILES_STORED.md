# 📦 Where Are Your Files Stored?

## 🎯 Quick Answer

**NEW FILES (uploaded after GridFS implementation):**
- ✅ Stored in **MongoDB Atlas** (cloud database)
- ✅ Collection: `fs.files` (metadata) + `fs.chunks` (file data)
- ✅ Accessible from **anywhere with internet**

**OLD FILES (uploaded before GridFS):**
- Still on local disk: `storage/` and `uploads/` folders
- Only accessible from the computer that has them

---

## 🗄️ MongoDB Storage Explained

### Where Exactly?

When you upload a file now, it goes to **2 MongoDB collections**:

#### 1. `fs.files` Collection (Metadata)
```javascript
{
  _id: ObjectId("67a1b2c3d4e5f6789abcdef0"),  // ← This is the file ID!
  length: 125000,                              // File size in bytes
  chunkSize: 261120,                           // 255KB per chunk
  uploadDate: ISODate("2025-09-30T10:30:00Z"),
  filename: "Screenshot.png",
  metadata: {
    contentType: "image/png",
    originalName: "Screenshot.png",
    projectId: "6833436be5c1677...",          // Which project
    uploadedBy: "6833436be5c1677...",         // Who uploaded it
    category: "image",
    uploadType: "chat"
  }
}
```

#### 2. `fs.chunks` Collection (Actual File Data)
```javascript
// For a 125KB image, you might have 1 chunk:
{
  _id: ObjectId("..."),
  files_id: ObjectId("67a1b2c3d4e5f6789abcdef0"),  // Links to fs.files
  n: 0,                                             // Chunk number (0 = first)
  data: BinData(0, "/9j/4AAQSkZJRg...")           // Base64 encoded image data
}

// For a 1MB PDF, you might have 4 chunks:
// Chunk 0: bytes 0-261119
// Chunk 1: bytes 261120-522239
// Chunk 2: bytes 522240-783359
// Chunk 3: bytes 783360-1000000
```

---

## 🔍 How to View Your Files in MongoDB

### Method 1: MongoDB Atlas Web UI

1. **Go to:** https://cloud.mongodb.com
2. **Login** to your account
3. **Select your cluster**
4. **Click "Browse Collections"**
5. **Look for collections:**
   - `fs.files` - Your file metadata
   - `fs.chunks` - Your file data

### Method 2: MongoDB Compass (Desktop App)

1. **Download:** https://www.mongodb.com/try/download/compass
2. **Connect** with your connection string
3. **Browse to `uiurp` database**
4. **View `fs.files` and `fs.chunks`**

### Method 3: MongoDB Shell

```javascript
// Connect to your database
use uiurp

// See all your files
db.fs.files.find().pretty()

// Count total files
db.fs.files.count()

// See file chunks
db.fs.chunks.find().limit(5).pretty()

// Find files by project
db.fs.files.find({ 
  "metadata.projectId": "6833436be5c1677..." 
}).pretty()

// Get total storage used
db.fs.files.aggregate([
  {
    $group: {
      _id: null,
      totalBytes: { $sum: "$length" },
      totalFiles: { $sum: 1 }
    }
  }
])
```

---

## 📊 File Storage Comparison

### Before GridFS (Old System)
```
Your Computer's Disk
└── /Users/test/uiurp/
    ├── uploads/
    │   └── chat_files/
    │       └── 685551a4793d650a9900a57f/
    │           └── chat_file_123456.png  ← FILE HERE (125KB)
    └── storage/
        └── files/
            └── file_789012.pdf            ← FILE HERE (2MB)

❌ Only accessible on this computer
❌ Friend can't access from their computer
❌ Doesn't work across networks
```

### After GridFS (New System)
```
MongoDB Atlas (Cloud)
└── uiurp database
    ├── fs.files
    │   ├── { _id: "...", filename: "image.png", length: 125000 }
    │   └── { _id: "...", filename: "doc.pdf", length: 2000000 }
    └── fs.chunks
        ├── { files_id: "...", n: 0, data: BinData(...) }  ← IMAGE DATA
        ├── { files_id: "...", n: 0, data: BinData(...) }  ← PDF CHUNK 1
        ├── { files_id: "...", n: 1, data: BinData(...) }  ← PDF CHUNK 2
        └── ...

✅ Accessible from ANY computer
✅ Works across different networks
✅ Your friend can access from their home
✅ Works from school, coffee shop, anywhere!
```

---

## 🔄 How Download Works

### When User Clicks Download:

1. **Browser sends request:**
   ```
   GET /download.php?gridfs_id=67a1b2c3d4e5f6789abcdef0
   ```

2. **download.php checks:**
   - Is user logged in? ✅
   - Is user authorized? ✅
   - Find file in MongoDB `fs.files`

3. **Stream from GridFS:**
   ```php
   // Open GridFS stream
   $stream = $bucket->openDownloadStream(gridfs_id);
   
   // Read chunks from fs.chunks
   // Stream to browser
   while (!feof($stream)) {
       echo fread($stream, 8192);
   }
   ```

4. **Browser receives file!**

---

## 💾 Storage Limits

### MongoDB Atlas Free Tier
- **512 MB** total storage
- **Shared cluster** (M0)
- Free forever!

### Your Current Usage
```javascript
// Run this in MongoDB shell to check:
db.fs.files.aggregate([
  {
    $group: {
      _id: null,
      totalSize: { $sum: "$length" },
      fileCount: { $sum: 1 }
    }
  },
  {
    $project: {
      totalSizeMB: { $divide: ["$totalSize", 1048576] },
      fileCount: 1
    }
  }
])

// Result:
// { totalSizeMB: 45.3, fileCount: 127 }
// You're using 45.3 MB out of 512 MB (9% used)
```

### What Can You Store?

With 512 MB:
- ~500 images (1 MB each)
- ~250 PDFs (2 MB each)
- ~50 videos (10 MB each)
- ~5,000 text documents (100 KB each)

---

## 🔐 Security

### Who Can Access Your Files?

**Files in GridFS are protected by:**

1. **Authentication:**
   - Must be logged in
   - Session required

2. **Authorization:**
   - Project members ✅
   - Supervisors ✅
   - Creators ✅
   - Others ❌

3. **Audit Log:**
   - Every download logged
   - Collection: `file_access_log`

### Network Access

**MongoDB Atlas Security:**
- ✅ Encrypted in transit (SSL/TLS)
- ✅ Encrypted at rest
- ✅ IP whitelist (if configured)
- ✅ Database authentication required
- ✅ Network-level security

Your files are **more secure** in MongoDB than on a local disk!

---

## 🎯 Key Differences

| Aspect | Local Disk (Old) | GridFS (New) |
|--------|------------------|--------------|
| **Location** | Your computer | MongoDB Cloud |
| **Access** | Single computer | Anywhere with internet |
| **Sharing** | Copy files manually | Automatic real-time |
| **Backup** | Manual | Automatic |
| **Security** | File permissions | DB authentication |
| **Cost** | Free (uses disk) | Free (Atlas free tier) |
| **Speed** | Very fast | Fast (network) |
| **Collaboration** | Difficult | Easy |

---

## 🐛 Debugging: "Where is my file?"

### Check if File is in GridFS

```javascript
// In MongoDB shell or Compass
db.fs.files.find({ filename: "your-file.png" }).pretty()

// If found, you'll see:
{
  _id: ObjectId("..."),
  filename: "your-file.png",
  // ... file is in GridFS ✅
}

// If not found:
// File might be in old system (local disk)
```

### Check File Download in Browser

1. **Open browser DevTools** (F12)
2. **Go to Network tab**
3. **Click download button**
4. **Look for request:**
   ```
   /download.php?gridfs_id=67a1b2c3d4e5f6789abcdef0
   ```
5. **Check response:**
   - 200 OK = Success ✅
   - 403 Forbidden = No permission ❌
   - 404 Not Found = File doesn't exist ❌

---

## 📈 Migration Status

### Current State

- ✅ **New uploads:** Go to GridFS automatically
- ⏳ **Old files:** Still on local disk (will migrate later)
- ✅ **System:** Supports both seamlessly

### Files Locations

Check your current files:
```bash
# Old files still on disk
ls -lh uploads/chat_files/*/
ls -lh storage/files/

# New files in MongoDB
# Use MongoDB Atlas or Compass to view
```

---

## ✅ Summary

**Where are files stored NOW?**

- 📦 **MongoDB Atlas Cloud** (`fs.files` + `fs.chunks` collections)
- 🌍 **Accessible from anywhere** with internet
- 🔒 **Secure** with authentication and authorization
- 💾 **512 MB free storage** included
- 🚀 **Real-time** for all users

**Your files are now in the cloud!** ☁️✨
