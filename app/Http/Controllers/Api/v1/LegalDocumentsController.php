<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LegalDocumentsController extends Controller
{
    /**
     * Получить все активные документы (публичный эндпоинт)
     * 
     * GET /api/v1/legal-documents
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $documents = Document::getActive();
        
        return response()->json([
            'data' => $documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'type' => $document->type,
                    'title' => $document->title,
                    'content' => $document->content,
                    'url' => $document->url,
                ];
            }),
        ]);
    }

    /**
     * Получить документ по типу (публичный эндпоинт)
     * 
     * GET /api/v1/legal-documents/{type}
     * 
     * @param string $type
     * @return JsonResponse
     */
    public function show(string $type): JsonResponse
    {
        $document = Document::getByType($type);
        
        if (!$document) {
            return response()->json([
                'message' => 'Документ не найден',
            ], 404);
        }
        
        return response()->json([
            'data' => [
                'id' => $document->id,
                'type' => $document->type,
                'title' => $document->title,
                'content' => $document->content,
                'url' => $document->url,
            ],
        ]);
    }

    /**
     * Получить все документы (админ)
     * 
     * GET /api/v1/admin/legal-documents
     * 
     * @return JsonResponse
     */
    public function getAdmin(): JsonResponse
    {
        $documents = Document::orderBy('sort_order')
            ->orderBy('title')
            ->get();
        
        return response()->json([
            'data' => $documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'type' => $document->type,
                    'title' => $document->title,
                    'content' => $document->content,
                    'url' => $document->url,
                    'is_active' => $document->is_active,
                    'sort_order' => $document->sort_order,
                    'created_at' => $document->created_at,
                    'updated_at' => $document->updated_at,
                ];
            }),
        ]);
    }

    /**
     * Обновить документы (админ)
     * 
     * PUT /api/v1/admin/legal-documents
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'documents' => 'required|array',
            'documents.*.id' => 'nullable|integer|exists:documents,id',
            'documents.*.type' => 'required|string|in:privacy_policy,offer,contacts',
            'documents.*.title' => 'required|string|max:255',
            'documents.*.content' => 'nullable|string',
            'documents.*.url' => 'nullable|url|max:1000',
            'documents.*.is_active' => 'boolean',
            'documents.*.sort_order' => 'integer|min:0',
        ], [
            'documents.required' => 'Документы обязательны',
            'documents.array' => 'Документы должны быть массивом',
            'documents.*.type.required' => 'Тип документа обязателен',
            'documents.*.type.in' => 'Недопустимый тип документа',
            'documents.*.title.required' => 'Название документа обязательно',
            'documents.*.title.max' => 'Название документа не должно превышать 255 символов',
            'documents.*.url.url' => 'URL должен быть валидным',
            'documents.*.url.max' => 'URL не должен превышать 1000 символов',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $documentsData = $validator->validated()['documents'];
            
            foreach ($documentsData as $docData) {
                if (isset($docData['id'])) {
                    // Обновляем существующий документ
                    $document = Document::findOrFail($docData['id']);
                    $document->fill([
                        'title' => $docData['title'],
                        'content' => $docData['content'] ?? null,
                        'url' => $docData['url'] ?? null,
                        'is_active' => $docData['is_active'] ?? true,
                        'sort_order' => $docData['sort_order'] ?? 0,
                    ]);
                    $document->save();
                } else {
                    // Создаем новый документ
                    // Проверяем, не существует ли уже документ с таким типом
                    $existing = Document::where('type', $docData['type'])->first();
                    if ($existing) {
                        // Обновляем существующий
                        $existing->fill([
                            'title' => $docData['title'],
                            'content' => $docData['content'] ?? null,
                            'url' => $docData['url'] ?? null,
                            'is_active' => $docData['is_active'] ?? true,
                            'sort_order' => $docData['sort_order'] ?? 0,
                        ]);
                        $existing->save();
                    } else {
                        // Создаем новый
                        Document::create([
                            'type' => $docData['type'],
                            'title' => $docData['title'],
                            'content' => $docData['content'] ?? null,
                            'url' => $docData['url'] ?? null,
                            'is_active' => $docData['is_active'] ?? true,
                            'sort_order' => $docData['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            $documents = Document::orderBy('sort_order')
                ->orderBy('title')
                ->get();
            
            return response()->json([
                'data' => $documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'type' => $document->type,
                        'title' => $document->title,
                        'content' => $document->content,
                        'url' => $document->url,
                        'is_active' => $document->is_active,
                        'sort_order' => $document->sort_order,
                        'created_at' => $document->created_at,
                        'updated_at' => $document->updated_at,
                    ];
                }),
                'message' => 'Документы успешно обновлены',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении документов: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Ошибка при обновлении документов',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
