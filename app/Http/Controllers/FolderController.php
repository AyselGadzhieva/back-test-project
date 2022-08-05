<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Folder;
use App\Models\FolderPermissions;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FolderController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $validate = $request->validate([
            'name'                      => 'required|string',
            'is_shared'                 => 'required|boolean',
            'is_protected'              => 'required|boolean',
            'parent_id'                 => 'sometimes|integer'
        ]);

        $validate['owner_id'] = auth()->id();

        if(auth()->user()->foldersUser()->find($request['parent_id'])){
            $validate['parent_id'] = $request['parent_id'];
        } else {$validate['parent_id'] = null;}

        $folder = Folder::query()->create($validate);

        $id = $folder->id;

        if($validate['is_shared']){
            $this->toShare($request['id_users'], $id);
        }

        return response()->json([
            'status' => 'Created'
        ], 201);
    }


    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(),[
            'name'            => 'sometimes|string',
            'id_users'        => 'sometimes',
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);
        }

        if ($folder = auth()->user()->foldersUser()->find($id)){
            $updated = $folder->update([
                'name' => $request->name
            ]);

            if($folder->is_shared){
                $this->updateToShare($request['id_users'], $folder);
            }

            if ($updated)
                return response()->json([
                    'message' => 'The folder was successfully updated'
                ], 200);
            else
                return response()->json([
                    'message' => 'The folder cannot be updated'
                ], 500);
        }

        return response()->json([
            'status' => 'Error'
        ], 422);
    }


    public function delete(int $id): JsonResponse
    {;
        $folder_owner = auth()->user()->foldersUser()->find($id);
        if ($folder_owner){
            $folder_owner->delete();
            return response()->json([
                'status' => 'Folder has been placed in the trash'
            ]);
        }
        return response()->json([
            'status' => 'Error'
        ], 422);
    }


    public function display(int $id): JsonResponse
    {
        $folder = auth()->user()->foldersUser()->find($id);
        if ($folder){
            $folder['documents'] = $folder->documentsFolder()->get()
                ->makeHidden(['document_name', 'owner_id', 'folder_id', 'document_type_id', 'size', 'deleted_at', 'updated_at'])->map(function ($value) {
                    $document_type = DocumentType::findOrFail($value->document_type_id);
                    $value['extension'] = $document_type->name;
                    $value['document_type'] = $document_type->description;
                    return  $value;
                });
            $folder['$folders'] = $folder->childrenFolders()->get();
            return response()->json($folder, 200);
        }
        return response()->json([
            'status' => 'Error'
        ], 422);
    }


    public function toShare(array $id_users, int $id)
    {
        $id_users = array_unique(array_diff($id_users, array(auth()->id())));
        foreach ($id_users as $value){
            $user_permission = new FolderPermissions;
            $user_permission->user_id = $value;
            $user_permission->folder_id = $id;
            $user_permission->save();
        }
    }


    public function updateToShare(array $id_users, Folder $folder)
    {
        $id_users = array_unique(array_diff($id_users, array(auth()->id())));
        $folder->usingFolders()->sync($id_users);
    }


    public function restore(int $id): JsonResponse
    {
        auth()->user()->foldersUser()->onlyTrashed()->findOrFail($id)->restore();
        return response()->json(['status' => 'Folder has been restored']);
    }

    public function show($slug){
        $searchString = '/';
        $start_page = new Collection();

        if(strpos($slug, $searchString) !== false) {
            $slug_array = explode('/',$slug);
        }

        if(isset($slug_array))
        {
            foreach($slug_array as $slug)
            {
                $folder = Folder::where('id', $slug)->with('childrenFoldersRecursive')->get()->map(function ($value) {
                    $value['documents'] = $value->documentsFolder()->get()
                        ->makeHidden(['document_name', 'owner_id', 'folder_id', 'document_type_id', 'size', 'deleted_at', 'updated_at'])->map(function ($value) {
                            $document_type = DocumentType::findOrFail($value->document_type_id);
                            $value['extension'] = $document_type->name;
                            $value['document_type'] = $document_type->description;
                            return  $value;
                        });
                    return  $value;
                });
                $start_page->put('folders', $folder);
            }
        }
        else
        {
            $folder = Folder::where('id', $slug)->with('childrenFoldersRecursive')->get()->map(function ($value) {
                $value['documents'] = $value->documentsFolder()->get()
                    ->makeHidden(['document_name', 'owner_id', 'folder_id', 'document_type_id', 'size', 'deleted_at', 'updated_at'])->map(function ($value) {
                        $document_type = DocumentType::findOrFail($value->document_type_id);
                        $value['extension'] = $document_type->name;
                        $value['document_type'] = $document_type->description;
                        return  $value;
                    });
                return  $value;
            });
            $start_page->put('folders', $folder);
        }
        return response()->json($start_page);
    }
}
