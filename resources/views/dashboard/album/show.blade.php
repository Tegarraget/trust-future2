@extends('dashboard.dashboard')

@section('title', $album['name'])

@section('content')
<div class="album-container">
    <!-- Album Header -->
    <div class="album-header glass-card">
        <div class="header-content">
            <div class="header-left">
                <a href="{{ route('dashboard') }}" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div class="album-title">
                    <h4 id="albumName">{{ $album['name'] }}</h4>
                    <button class="edit-title-btn" onclick="showEditNameForm()">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
                <form id="editNameForm" action="{{ route('album.updateName', $album['id']) }}" 
                      method="POST" class="d-none">
                    @csrf
                    @method('PATCH')
                    <div class="edit-name-group">
                        <input type="text" name="name" value="{{ $album['name'] }}" required>
                        <div class="edit-actions">
                            <button type="submit" class="save-btn">
                                <i class="bi bi-check"></i>
                            </button>
                            <button type="button" class="cancel-btn" onclick="hideEditNameForm()">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <button class="upload-btn" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                <i class="bi bi-plus-lg"></i>
                <span>Upload Foto</span>
            </button>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert success-alert">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert error-alert">
            <i class="bi bi-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Photos Grid -->
    <div class="photos-grid">
        @foreach($album['photos'] as $photo)
        <div class="photo-card">
            <div class="photo-wrapper">
                <img src="{{ $photo['url'] }}" alt="{{ $photo['name'] }}" loading="lazy">
                <div class="photo-overlay">
                    <div class="photo-actions">
                        <button onclick="previewImage('{{ $photo['url'] }}')" class="action-btn preview-btn">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button onclick="showEditPhotoModal('{{ $photo['id'] }}', '{{ $photo['url'] }}')" class="action-btn edit-btn">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('album.deletePhoto', ['albumId' => $album['id'], 'photoId' => $photo['id']]) }}" 
                              method="POST"
                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus foto ini?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="filename" value="{{ $photo['filename'] }}">
                            <button type="submit" class="action-btn delete-btn">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="photo-info">
                <span id="photoName_{{ $photo['id'] }}" class="photo-name">{{ $photo['name'] }}</span>
                <button class="edit-photo-btn" onclick="showEditPhotoName('{{ $photo['id'] }}', '{{ $photo['name'] }}')">
                    <i class="bi bi-pencil"></i>
                </button>
                <form id="editPhotoForm_{{ $photo['id'] }}" 
                      action="{{ route('album.updatePhotoName', ['albumId' => $album['id'], 'photoId' => $photo['id']]) }}" 
                      method="POST" 
                      class="d-none photo-edit-form">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="filename" value="{{ $photo['filename'] }}">
                    <div class="edit-photo-group">
                        <input type="text" name="name" value="{{ $photo['name'] }}" required>
                        <div class="edit-actions">
                            <button type="submit" class="save-btn">
                                <i class="bi bi-check"></i>
                            </button>
                            <button type="button" class="cancel-btn" onclick="hideEditPhotoName('{{ $photo['id'] }}')">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Foto Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('album.upload', $album['id']) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="upload-area">
                        <label for="photos" class="upload-label">
                            <i class="bi bi-cloud-upload"></i>
                            <span>Pilih foto atau drag & drop di sini</span>
                            <small>Format: JPG, JPEG, PNG, GIF (Max: 2MB)</small>
                        </label>
                        <input type="file" id="photos" name="photos[]" accept="image/*" multiple required>
                    </div>
                    <div id="previewContainer" class="preview-grid"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn cancel-btn" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn upload-btn">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="photoPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="preview-image-container">
                    <img src="" id="previewImage" alt="Preview">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Photo Modal -->
<div class="modal fade" id="editPhotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Foto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPhotoImageForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="current-photo mb-3">
                        <label class="form-label">Foto Saat Ini</label>
                        <img id="currentPhoto" src="" alt="Current Photo" class="img-fluid rounded">
                    </div>
                    <div class="mb-3">
                        <label for="newPhoto" class="form-label">Pilih Foto Baru</label>
                        <input type="file" class="form-control" id="newPhoto" name="photo" accept="image/*" required>
                    </div>
                    <div class="preview-container mb-3 d-none">
                        <label class="form-label">Preview</label>
                        <img id="photoPreview" src="" alt="Preview" class="img-fluid rounded">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn cancel-btn" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn save-btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.album-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.album-header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.back-btn {
    color: white;
    font-size: 1.2rem;
    transition: transform 0.3s;
}

.back-btn:hover {
    transform: translateX(-5px);
}

.album-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.album-title h4 {
    margin: 0;
    color: white;
}

.edit-title-btn, .edit-photo-btn {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    padding: 5px;
    transition: color 0.3s;
}

.edit-title-btn:hover, .edit-photo-btn:hover {
    color: white;
}

.upload-btn {
    background: linear-gradient(45deg, #2196F3, #00BCD4);
    border: none;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.upload-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(33, 150, 243, 0.3);
}

.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
    padding: 20px 0;
}

.photo-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s;
}

.photo-card:hover {
    transform: translateY(-5px);
}

.photo-wrapper {
    position: relative;
    padding-top: 75%;
}

.photo-wrapper img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.photo-wrapper:hover .photo-overlay {
    opacity: 1;
}

.photo-actions {
    display: flex;
    gap: 15px;
}

.action-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s;
}

.preview-btn {
    background: white;
    color: #2196F3;
}

.delete-btn {
    background: #FF5252;
    color: white;
}

.action-btn:hover {
    transform: scale(1.1);
}

.photo-info {
    padding: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.photo-name {
    color: white;
    font-size: 0.9rem;
}

/* Alert Styles */
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.success-alert {
    background: rgba(76, 175, 80, 0.2);
    border: 1px solid #4CAF50;
    color: #4CAF50;
}

.error-alert {
    background: rgba(244, 67, 54, 0.2);
    border: 1px solid #F44336;
    color: #F44336;
}

/* Upload Modal Styles */
.upload-area {
    border: 2px dashed rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    margin-bottom: 20px;
}

.upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.upload-label i {
    font-size: 2rem;
    color: rgba(255, 255, 255, 0.7);
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    margin-top: 20px;
}

/* Edit Forms */
.edit-name-group, .edit-photo-group {
    display: flex;
    gap: 10px;
}

.edit-name-group input, .edit-photo-group input {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 5px;
    padding: 5px 10px;
    color: white;
}

.edit-actions {
    display: flex;
    gap: 5px;
}

.save-btn, .cancel-btn {
    padding: 5px 10px;
    border: none;
    border-radius: 5px;
}

.save-btn {
    background: #4CAF50;
    color: white;
}

.cancel-btn {
    background: #F44336;
    color: white;
}

/* Preview Modal Styles */
.preview-image-container {
    position: relative;
    width: 100%;
    max-height: 80vh;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

.preview-image-container img {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
    margin: auto;
}

#photoPreviewModal .modal-content {
    background: rgba(0, 0, 0, 0.9);
}

#photoPreviewModal .modal-header {
    position: absolute;
    top: 0;
    right: 0;
    z-index: 1;
    background: transparent;
}

#photoPreviewModal .btn-close-white {
    filter: brightness(0) invert(1);
    opacity: 0.8;
}

#photoPreviewModal .btn-close-white:hover {
    opacity: 1;
}

.edit-btn {
    background: #FFC107;
    color: white;
}

#editPhotoModal .modal-content {
    background: #1a1a1a;
    color: white;
}

#editPhotoModal .form-label {
    color: white;
}

#editPhotoModal .form-control {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    color: white;
}

#editPhotoModal .current-photo img,
#editPhotoModal .preview-container img {
    max-height: 200px;
    width: auto;
    display: block;
    margin: 0 auto;
}

#editPhotoModal .modal-header {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

#editPhotoModal .modal-footer {
    border-top-color: rgba(255, 255, 255, 0.1);
}
</style>

<script>
// Existing JavaScript functions remain the same
function previewImage(url) {
    document.getElementById('previewImage').src = url;
    new bootstrap.Modal(document.getElementById('photoPreviewModal')).show();
}

function showEditNameForm() {
    document.getElementById('albumName').parentElement.style.display = 'none';
    document.getElementById('editNameForm').classList.remove('d-none');
}

function hideEditNameForm() {
    document.getElementById('albumName').parentElement.style.display = 'flex';
    document.getElementById('editNameForm').classList.add('d-none');
}

function showEditPhotoName(photoId) {
    document.getElementById(`photoName_${photoId}`).style.display = 'none';
    document.getElementById(`editPhotoForm_${photoId}`).classList.remove('d-none');
}

function hideEditPhotoName(photoId) {
    document.getElementById(`photoName_${photoId}`).style.display = 'inline';
    document.getElementById(`editPhotoForm_${photoId}`).classList.add('d-none');
}

// Enhanced file upload preview
document.getElementById('photos').addEventListener('change', function(e) {
    const previewContainer = document.getElementById('previewContainer');
    previewContainer.innerHTML = '';
    
    Array.from(e.target.files).forEach(file => {
        if (file) {
            const reader = new FileReader();
            const previewDiv = document.createElement('div');
            previewDiv.className = 'preview-item';
            previewDiv.style.cssText = `
                aspect-ratio: 1;
                border-radius: 8px;
                overflow: hidden;
                position: relative;
            `;
            
            const img = document.createElement('img');
            img.style.cssText = `
                width: 100%;
                height: 100%;
                object-fit: cover;
            `;
            
            reader.onload = function(e) {
                img.src = e.target.result;
            }
            
            reader.readAsDataURL(file);
            previewDiv.appendChild(img);
            previewContainer.appendChild(previewDiv);
        }
    });
});

function showEditPhotoModal(photoId, photoUrl) {
    const modal = document.getElementById('editPhotoModal');
    const form = document.getElementById('editPhotoImageForm');
    const currentPhoto = document.getElementById('currentPhoto');
    
    // Set current photo
    currentPhoto.src = photoUrl;
    
    // Set form action URL dengan route yang benar
    form.action = `/album/photo/${photoId}/update-image`;
    
    // Show modal
    new bootstrap.Modal(modal).show();
}

// Preview foto baru yang dipilih
document.getElementById('newPhoto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('photoPreview');
    const previewContainer = document.querySelector('.preview-container');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.classList.remove('d-none');
        }
        reader.readAsDataURL(file);
    } else {
        previewContainer.classList.add('d-none');
    }
});
</script>
@endsection 