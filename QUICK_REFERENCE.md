# Quick Reference: Tailwind CSS Migration

## 🎯 Template Konversi Cepat

### Header HTML (Ganti di semua file)
```html
<!-- HAPUS INI -->
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/responsive.css?v=4">

<!-- TIDAK PERLU DITAMBAH (sudah auto-inject dari main.js) -->
<!-- Tailwind dan Boxicons sudah otomatis di-load oleh main.js -->
```

### Konversi Class Umum

#### Containers
```html
<!-- LAMA -->
<div class="glass-panel">

<!-- BARU -->
<div class="bg-slate-800/70 backdrop-blur-xl border border-white/10 rounded-2xl p-6 shadow-xl">
```

#### Headers
```html
<!-- LAMA -->
<h3 class="mb-3">Title</h3>

<!-- BARU -->
<h3 class="text-2xl font-bold text-white mb-4">Title</h3>
```

#### Tables
```html
<!-- LAMA -->
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Column</th>
            </tr>
        </thead>
        <tbody>
            <td>Data</td>
        </tbody>
    </table>
</div>

<!-- BARU -->
<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="border-b border-white/10">
                <th class="text-left py-3 px-4 text-sm font-semibold text-slate-300">Column</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            <td class="py-3 px-4 text-white">Data</td>
        </tbody>
    </table>
</div>
```

#### Forms
```html
<!-- LAMA -->
<div class="form-group">
    <label class="form-label">Label</label>
    <input type="text" class="form-input">
</div>

<!-- BARU -->
<div class="mb-4">
    <label class="block text-sm font-medium text-slate-300 mb-2">Label</label>
    <input type="text" class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
</div>
```

#### Buttons
```html
<!-- LAMA -->
<button class="btn btn-primary">Click</button>

<!-- BARU -->
<button class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">Click</button>
```

#### Modals
```html
<!-- LAMA -->
<div id="modal" class="hidden" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:2000; display:flex; align-items:center; justify-content:center;">
    <div class="glass-panel" style="width: 100%; max-width: 450px;">
        <h3>Modal Title</h3>
        <!-- content -->
    </div>
</div>

<!-- BARU -->
<div id="modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-[2000] flex items-center justify-center p-4">
    <div class="bg-slate-800/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-xl font-bold text-white mb-6">Modal Title</h3>
        <!-- content -->
    </div>
</div>
```

---

## 📝 Checklist Konversi Per File

### ✅ Sudah Selesai:
- [x] index.html
- [x] dashboard.html
- [x] input.html
- [x] riwayat.html
- [x] users.html
- [x] print_izin.html
- [x] main.js
- [x] tailwind-custom.css

### 📋 Perlu Dikonversi:
- [ ] santri.html
- [ ] laporan.html
- [ ] perizinan.html

---

## 🔧 Cara Konversi File Tersisa

### Untuk santri.html:
1. Hapus link ke style.css dan responsive.css
2. Ganti semua `.glass-panel` dengan Tailwind classes
3. Update semua modal (ada 3 modal: Add, Edit, Raport)
4. Update table styling
5. Update badge classes dengan Tailwind
6. Pastikan clickable-name tetap berfungsi

### Untuk laporan.html:
1. Hapus link CSS lama
2. Update filter grid dengan Tailwind grid
3. Update table dan export buttons
4. Pastikan print styles tetap berfungsi

### Untuk perizinan.html:
1. Hapus link CSS lama
2. Update tab navigation dengan Tailwind
3. Update semua 4 sections (dashboard, input, data, settings)
4. Update stats cards
5. Update forms dan tables

---

## 🎨 Custom Styles yang Perlu Dipertahankan

Beberapa style custom yang tetap perlu ada di `<style>` tag:

```html
<style>
    /* Badge colors (untuk santri.html) */
    .badge-danger {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }
    
    .badge-success {
        background: rgba(34, 197, 94, 0.2);
        color: #22c55e;
    }
    
    .badge-warning {
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
    }
    
    /* Clickable name (untuk santri.html) */
    .clickable-name {
        color: #6366f1;
        cursor: pointer;
        text-decoration: underline;
    }
    
    .clickable-name:hover {
        color: white;
    }
    
    /* Tab styles (untuk perizinan.html) */
    .tab-btn.active {
        background: #6366f1;
        color: white;
    }
</style>
```

---

## 💡 Tips Penting

1. **Jangan hapus JavaScript** - Hanya ganti HTML/CSS
2. **Test di mobile** - Buka di browser, tekan F12, pilih device toolbar
3. **Gunakan responsive classes** - `sm:`, `md:`, `lg:`
4. **Pertahankan ID dan onclick** - Untuk JavaScript tetap berfungsi
5. **Copy-paste dengan hati-hati** - Pastikan tidak ada tag yang hilang

---

## 🚀 Hasil Akhir yang Diharapkan

✅ Semua halaman responsive di mobile, tablet, dan desktop
✅ Design konsisten dengan glassmorphism dan gradient
✅ Smooth transitions dan hover effects
✅ Semua fungsi JavaScript tetap berfungsi
✅ Print functionality tetap bekerja
✅ Fast loading dengan Tailwind JIT

---

## 📞 Support

Jika ada masalah:
1. Check browser console (F12) untuk error JavaScript
2. Pastikan semua closing tags lengkap
3. Verify bahwa main.js sudah load Tailwind CDN
4. Test di browser yang berbeda

---

**Status:** 8/11 files completed (73%)
**Estimasi waktu tersisa:** ~30-45 menit untuk 3 file terakhir
