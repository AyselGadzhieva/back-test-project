<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Folder;
use App\Models\User;
use App\Models\FolderPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Document;
use App\Models\DocumentPermissions;
use App\Models\DocumentType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PerformanceController extends Controller
{
    public function index(): JsonResponse
    {
        $hide_fields_document = ['document_name', 'owner_id', 'folder_id', 'document_type_id', 'size', 'deleted_at', 'updated_at'];
        $start_page = new Collection();
        $start_page_folder = auth()->user()->foldersUser()->whereNull('parent_id')
            ->get();
        $start_page_document = auth()->user()->documentsUser()
            ->whereNull('folder_id')
            ->get()
            ->makeHidden($hide_fields_document);
        foreach ($start_page_document as $document){
            $document_type = DocumentType::findOrFail($document->document_type_id);
            $document['extension'] = $document_type->name;
            $document['document_type'] = $document_type->description;
        }
        $start_page->put('folders', $start_page_folder);
        $start_page->put('documents', $start_page_document);
        return response()->json($start_page);
    }


    public function recycleBin(): JsonResponse
    {
        $recycle_bin = new Collection();
        $hide_fields_document = ['document_name', 'owner_id', 'folder_id', 'document_type_id', 'size', 'deleted_at', 'updated_at'];
        $recycle_bin_document = auth()->user()
            ->documentsUser()->onlyTrashed()
            ->get()->makeHidden($hide_fields_document)->map(function ($value) {
                $document_type = DocumentType::findOrFail($value->document_type_id);
                $value['extension'] = $document_type->name;
                $value['document_type'] = $document_type->description;
                return  $value;
            });
        $recycle_bin_folder = auth()->user()
            ->foldersUser()->onlyTrashed()->get();
        $recycle_bin->put('folders', $recycle_bin_folder);
        $recycle_bin->put('documents', $recycle_bin_document);
        return response()->json($recycle_bin, 200);
    }

    public function shared(): JsonResponse
    {
        $shared_folders = auth()->user()->Folders()->get()->map(function ($value) {
                unset($value->pivot);
                return  $value;
            });
        $hide_fields_document = ['document_name', 'owner_id', 'folder_id', 'document_type_id', 'size', 'deleted_at', 'updated_at'];
        $shared_documents = auth()->user()->Documents()->get()->makeHidden($hide_fields_document)->map(function ($value) {
            unset($value->pivot);
            $document_type = DocumentType::findOrFail($value->document_type_id);
            $value['extension'] = $document_type->name;
            $value['document_type'] = $document_type->description;
            return  $value;
        });
        $shared = new Collection();
        $shared->put('folders', $shared_folders);
        $shared->put('documents', $shared_documents);
        return response()->json($shared, 200);
    }

    public function search($search_parameter): JsonResponse
    {
        $user_id = auth()->id();

        $search1 = DB::table('document_permissions')
            ->join('users', 'users.id', '=', 'document_permissions.user_id')
            ->join('documents', 'documents.id', '=', 'document_permissions.document_id')
            ->join('document_types as dt', 'dt.id', '=', 'documents.document_type_id')
            ->where('document_permissions.user_id', $user_id)
            ->where(function ($query) use ($search_parameter) {
                $query->where('documents.id', 'like', '%'.$search_parameter.'%')
                    ->orWhere('documents.name', 'like', '%'.$search_parameter.'%')
                    ->orWhere('documents.is_protected', 'like', '%'.$search_parameter.'%')
                    ->orWhere('documents.is_shared', 'like', '%'.$search_parameter.'%');
            })
            ->select(['documents.id', 'documents.name', 'documents.is_shared', 'documents.is_protected', 'documents.created_at', 'dt.name as extension', 'dt.description']);

        $search2 = DB::table('folder_permissions')
            ->join('users', 'users.id', '=', 'folder_permissions.user_id')
            ->join('folders', 'folders.id', '=', 'folder_permissions.folder_id')
            ->where('folder_permissions.user_id', $user_id)
            ->where(function ($query) use ($search_parameter) {
                $query->where('folders.id', 'like', '%'.$search_parameter.'%')
                    ->orWhere('folders.name', 'like', '%'.$search_parameter.'%')
                    ->orWhere('folders.is_protected', 'like', '%'.$search_parameter.'%')
                    ->orWhere('folders.is_shared', 'like', '%'.$search_parameter.'%');
            })
            ->select(['folders.id', 'folders.name', 'folders.is_shared', 'folders.is_protected']);

        $search3 = DB::table('folders')
            ->where('owner_id', $user_id)
            ->where(function ($query) use ($search_parameter) {
                $query->where('id', 'like', '%'.$search_parameter.'%')
                      ->orWhere('name', 'like', '%'.$search_parameter.'%')
                      ->orWhere('is_protected', 'like', '%'.$search_parameter.'%')
                      ->orWhere('is_shared', 'like', '%'.$search_parameter.'%');
            })
            ->select(['id', 'name', 'is_shared', 'is_protected'])
            ->union($search2)
            ->get();

        $search4 = DB::table('documents as d')
            ->join('document_types as dt', 'dt.id', '=', 'd.document_type_id')
            ->where('d.owner_id', $user_id)
            ->where(function ($query) use ($search_parameter) {
                $query->where('d.id', 'like', '%'.$search_parameter.'%')
                      ->orWhere('d.name', 'like', '%'.$search_parameter.'%')
                      ->orWhere('d.is_protected', 'like', '%'.$search_parameter.'%')
                      ->orWhere('d.is_shared', 'like', '%'.$search_parameter.'%')
                      ->orWhere('d.created_at', 'like', '%'.$search_parameter.'%');
            })
            ->select(['d.id', 'd.name', 'd.is_shared', 'd.is_protected', 'd.created_at', 'dt.name as extension', 'dt.description'])
            ->union($search1)
            ->get();

        $search = new Collection();
        $search->put('folders', $search3);
        $search->put('documents', $search4);

        return response()->json($search);
    }
}
