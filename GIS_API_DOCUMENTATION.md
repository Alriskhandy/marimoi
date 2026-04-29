# GIS REST API Documentation

## Base URL
```
/api/gis/
```

## Features API

### List Features
```
GET /api/gis/features
```
**Query Parameters:**
- `layer_id` (int) - Filter by layer ID
- `user_id` (int) - Filter by user ID
- `year` (int) - Filter by year
- `search` (string) - Search in properties or UUID
- `layer_type` (string) - Filter by layer type (point, line, polygon)
- `per_page` (int) - Items per page (max 500)

### Create Feature
```
POST /api/gis/features
```
**Body:**
```json
{
  "layer_id": 1,
  "user_id": 1,
  "properties": {"name": "Feature Name"},
  "year": 2024,
  "uuid": "auto-generated-if-empty",
  "images": [
    {"file_path": "/path/to/image.jpg", "caption": "Image caption"}
  ]
}
```

### Get Feature by ID
```
GET /api/gis/features/{id}
```

### Get Feature by UUID
```
GET /api/gis/features/uuid/{uuid}
```

### Update Feature
```
PUT /api/gis/features/{id}
```

### Delete Feature
```
DELETE /api/gis/features/{id}
```

### Additional Feature Endpoints
- `GET /api/gis/features/layer/{layerId}` - Get features by layer
- `GET /api/gis/features/user/{userId}` - Get features by user
- `GET /api/gis/features/bounds?min_lng=-180&min_lat=-90&max_lng=180&max_lat=90` - Get features within bounds
- `POST /api/gis/features/{id}/views` - Increment feature views
- `GET /api/gis/features/{id}/images` - Get feature images
- `POST /api/gis/features/{id}/images` - Add image to feature
- `DELETE /api/gis/features/{featureId}/images/{imageId}` - Remove image from feature
- `GET /api/gis/features/{id}/with-images` - Get feature with images count
- `GET /api/gis/features/statistics/all` - Get features statistics
- `POST /api/gis/features/bulk` - Bulk create features

## Layers API

### List Layers
```
GET /api/gis/layers
```
**Query Parameters:**
- `type` (string) - Filter by type (point, line, polygon)
- `user_id` (int) - Filter by user ID
- `is_active` (boolean) - Filter by active status
- `parent_id` (int) - Filter by parent ID
- `search` (string) - Search in name
- `per_page` (int) - Items per page (max 500)

### Create Layer
```
POST /api/gis/layers
```
**Body:**
```json
{
  "name": "Layer Name",
  "type": "point",
  "style": {"color": "#ff0000"},
  "parent_id": null,
  "is_active": true,
  "user_id": 1
}
```

### Get Layer by ID
```
GET /api/gis/layers/{id}
```

### Update Layer
```
PUT /api/gis/layers/{id}
```

### Delete Layer
```
DELETE /api/gis/layers/{id}
```

### Additional Layer Endpoints
- `GET /api/gis/layers/roots` - Get root layers (no parent)
- `GET /api/gis/layers/tree` - Get complete layer tree
- `GET /api/gis/layers/{parentId}/children` - Get layer children
- `GET /api/gis/layers/user/{userId}` - Get layers by user
- `GET /api/gis/layers/active/all` - Get active layers
- `GET /api/gis/layers/type/{type}` - Get layers by type
- `POST /api/gis/layers/{id}/toggle-active` - Toggle layer active status
- `POST /api/gis/layers/{id}/move` - Move layer to new parent
- `GET /api/gis/layers/{id}/with-features` - Get layer with features count
- `GET /api/gis/layers/statistics/all` - Get layers statistics
- `POST /api/gis/layers/{id}/duplicate` - Duplicate layer with features
- `POST /api/gis/layers/bulk` - Bulk create layers

## Feature Images API

### List Images
```
GET /api/gis/images
```
**Query Parameters:**
- `feature_id` (int) - Filter by feature ID
- `layer_id` (int) - Filter by layer ID
- `user_id` (int) - Filter by user ID
- `search` (string) - Search in caption
- `per_page` (int) - Items per page (max 500)

### Create Image
```
POST /api/gis/images
```
**Body:**
```json
{
  "feature_id": 1,
  "file_path": "/path/to/image.jpg",
  "caption": "Image caption"
}
```

### Upload Image
```
POST /api/gis/images/upload/{featureId}
```
**Form Data:**
- `image` (file) - Image file (max 5MB, jpeg,jpg,png,gif,webp)
- `caption` (string) - Optional caption

### Bulk Upload Images
```
POST /api/gis/images/bulk-upload/{featureId}
```
**Form Data:**
- `images[]` (file[]) - Multiple image files
- `captions[]` (string[]) - Optional captions array

### Get Image by ID
```
GET /api/gis/images/{id}
```

### Update Image
```
PUT /api/gis/images/{id}
```

### Delete Image
```
DELETE /api/gis/images/{id}
```

### Additional Image Endpoints
- `GET /api/gis/images/feature/{featureId}` - Get images by feature
- `GET /api/gis/images/layer/{layerId}` - Get images by layer
- `GET /api/gis/images/user/{userId}` - Get images by user
- `POST /api/gis/images/bulk` - Bulk create images
- `GET /api/gis/images/{id}/exists` - Check if file exists
- `GET /api/gis/images/{id}/url` - Get file URL
- `GET /api/gis/images/statistics/all` - Get images statistics
- `POST /api/gis/images/cleanup` - Cleanup orphaned files

## Response Format

All API responses follow this format:

**Success Response:**
```json
{
  "success": true,
  "data": {...},
  "meta": {...},
  "message": "Optional message"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": {...}
}
```

**Validation Error Response:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

## Authentication

All endpoints require proper authentication. Make sure to include authentication headers in your requests.

## Rate Limiting

API endpoints are subject to rate limiting. Please implement appropriate retry logic with exponential backoff.