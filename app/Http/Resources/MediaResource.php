<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $metadata = $this->metadata ? json_decode($this->metadata, true) : [];
        
        $variants = [];
        if ($this->type === 'photo' && isset($metadata['variants'])) {
            foreach ($metadata['variants'] as $variantName => $variantData) {
                $variants[$variantName] = [
                    'webp' => $variantData['webp'] ? '/' . ltrim($variantData['webp'], '/') : null,
                    'jpeg' => $variantData['jpeg'] ? '/' . ltrim($variantData['jpeg'], '/') : null,
                    'width' => $variantData['width'] ?? null,
                    'height' => $variantData['height'] ?? null,
                ];
            }
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'original_name' => $this->original_name,
            'extension' => $this->extension,
            'disk' => $this->disk,
            'width' => $this->width,
            'height' => $this->height,
            'type' => $this->type,
            'size' => $this->size,
            'folder_id' => $this->folder_id,
            'user_id' => $this->user_id,
            'telegram_file_id' => $this->telegram_file_id,
            'temporary' => $this->temporary,
            'url' => '/' . ($metadata['path'] ?? ($this->disk . '/' . $this->name)),
            'webp_url' => $this->type === 'photo' && isset($metadata['webp_path']) 
                ? '/' . ltrim($metadata['webp_path'], '/') 
                : null,
            'thumbnail' => $this->type === 'photo' ? ('/' . ($metadata['path'] ?? ($this->disk . '/' . $this->name))) : null,
            'variants' => !empty($variants) ? $variants : null,
            'original_folder_id' => $this->original_folder_id,
            'deleted_at' => $this->deleted_at,
            'is_in_trash' => $this->folder_id == 4, // ID корзины
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'folder' => $this->whenLoaded('folder', function() {
                return new FolderResource($this->folder);
            }),
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
        ];
    }
}
