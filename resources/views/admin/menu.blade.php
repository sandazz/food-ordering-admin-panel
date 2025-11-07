@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Menu Management</h1>
        <p class="page-subtitle">Manage your restaurant's menu items, categories, and pricing</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary">
            <i class="bi bi-upload me-2"></i>Import Menu
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="bi bi-plus-lg me-2"></i>Add Menu Item
        </button>
    </div>
</div>

<!-- Menu Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stats-card primary">
            <div class="card-body text-center">
                <div class="icon mx-auto">
                    <i class="bi bi-card-list"></i>
                </div>
                <h4 class="mb-1">24</h4>
                <p class="text-muted mb-0">Total Items</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card success">
            <div class="card-body text-center">
                <div class="icon mx-auto">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h4 class="mb-1">22</h4>
                <p class="text-muted mb-0">Active Items</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card warning">
            <div class="card-body text-center">
                <div class="icon mx-auto">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h4 class="mb-1">2</h4>
                <p class="text-muted mb-0">Out of Stock</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card info">
            <div class="card-body text-center">
                <div class="icon mx-auto">
                    <i class="bi bi-grid-3x3-gap"></i>
                </div>
                <h4 class="mb-1">6</h4>
                <p class="text-muted mb-0">Categories</p>
            </div>
        </div>
    </div>
</div>

<!-- Menu Categories Tabs -->
<div class="card mb-4">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="categoryTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#all-items">
                    <i class="bi bi-grid-3x3-gap me-2"></i>All Items
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#appetizers">
                    <i class="bi bi-egg-fried me-2"></i>Appetizers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#mains">
                    <i class="bi bi-cup-hot me-2"></i>Main Courses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#desserts">
                    <i class="bi bi-cup-hot me-2"></i>Desserts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#beverages">
                    <i class="bi bi-cup-straw me-2"></i>Beverages
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <!-- Search and Filter Bar -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control" placeholder="Search menu items...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="out-of-stock">Out of Stock</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select">
                    <option value="">Sort by</option>
                    <option value="name">Name A-Z</option>
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                    <option value="popularity">Popularity</option>
                </select>
            </div>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="all-items">
                <!-- Menu Items Grid -->
                <div class="row g-3">
                    @for($i = 1; $i <= 8; $i++)
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="card h-100">
                            <div class="position-relative">
                                <img src="https://via.placeholder.com/250x150/6366f1/ffffff?text=Food+{{ $i }}" class="card-img-top" alt="Food Item">
                                <div class="position-absolute top-0 end-0 p-2">
                                    @if($i % 3 === 0)
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @else
                                        <span class="badge bg-success">Available</span>
                                    @endif
                                </div>
                                <div class="position-absolute bottom-0 start-0 p-2">
                                    <span class="badge bg-primary">{{ $i % 2 === 0 ? 'Popular' : 'New' }}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title mb-2">Delicious Food Item {{ $i }}</h6>
                                <p class="card-text text-muted small mb-2">A wonderful description of this amazing food item that customers will love.</p>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-primary">${{ number_format(12.99 + $i, 2) }}</span>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-star-fill text-warning me-1"></i>
                                        <small class="text-muted">4.{{ 5 + ($i % 4) }}</small>
                                    </div>
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-primary flex-fill">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success flex-fill">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary flex-fill">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                    <div class="dropdown flex-fill">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#"><i class="bi bi-toggles me-2"></i>Toggle Status</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="bi bi-files me-2"></i>Duplicate</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endfor
                </div>

                <!-- Pagination -->
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            </div>
            
            <div class="tab-pane fade" id="appetizers">
                <div class="text-center py-5">
                    <i class="bi bi-egg-fried text-muted mb-3" style="font-size: 3rem;"></i>
                    <h6 class="text-muted">Appetizers will be displayed here</h6>
                </div>
            </div>
            
            <div class="tab-pane fade" id="mains">
                <div class="text-center py-5">
                    <i class="bi bi-cup-hot text-muted mb-3" style="font-size: 3rem;"></i>
                    <h6 class="text-muted">Main courses will be displayed here</h6>
                </div>
            </div>
            
            <div class="tab-pane fade" id="desserts">
                <div class="text-center py-5">
                    <i class="bi bi-heart text-muted mb-3" style="font-size: 3rem;"></i>
                    <h6 class="text-muted">Desserts will be displayed here</h6>
                </div>
            </div>
            
            <div class="tab-pane fade" id="beverages">
                <div class="text-center py-5">
                    <i class="bi bi-cup-straw text-muted mb-3" style="font-size: 3rem;"></i>
                    <h6 class="text-muted">Beverages will be displayed here</h6>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Panel -->
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-grid-3x3-gap me-2"></i>Manage Categories
                    </button>
                    <button class="btn btn-outline-success btn-sm">
                        <i class="bi bi-tags me-2"></i>Bulk Price Update
                    </button>
                    <button class="btn btn-outline-info btn-sm">
                        <i class="bi bi-download me-2"></i>Export Menu
                    </button>
                    <button class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-arrow-repeat me-2"></i>Sync Inventory
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Popular Items</h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @for($i = 1; $i <= 3; $i++)
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="https://via.placeholder.com/40x40/28a745/ffffff?text={{ $i }}" class="rounded me-2" alt="Food">
                            <div>
                                <h6 class="mb-0">Popular Item {{ $i }}</h6>
                                <small class="text-muted">{{ 50 + ($i * 15) }} orders</small>
                            </div>
                        </div>
                        <span class="badge bg-success rounded-pill">{{ 95 - ($i * 5) }}%</span>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Low Stock Alerts</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>2 items</strong> are running low on stock
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Chicken Wings</h6>
                            <small class="text-danger">Only 5 left</small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary">Restock</button>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Caesar Salad</h6>
                            <small class="text-danger">Only 3 left</small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary">Restock</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" placeholder="Enter item name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select class="form-select">
                                <option>Appetizers</option>
                                <option>Main Courses</option>
                                <option>Desserts</option>
                                <option>Beverages</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" placeholder="Describe your menu item"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prep Time (minutes)</label>
                            <input type="number" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select">
                                <option>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" accept="image/*">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Add Item</button>
            </div>
        </div>
    </div>
</div>
@endsection
