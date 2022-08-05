<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPermissions;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),[
            'document'              => 'required|mimes:png,jpg,xls,xlsx,doc,docx,txt|max:10000',
            'is_shared'             => 'required|boolean',
            'is_protected'          => 'required|boolean',
            'folder_id'             => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);
        }

        if ($document = $request->file('document')) {
            $document_name = $document->hashName();
            $path = $document->storeAs('document', $document_name);
            $document_type_id = DB::table('document_types')
            ->where('name', $document->extension())
            ->pluck('id')->first();

            $folder_id = null;
            $folder = auth()->user()->foldersUser()->find($request['folder_id']);
            if ($folder != null) {
                $folder_id = $folder->id;
            }

            $download_link = public_path('storage/document/' . $document_name);

            $document_create = Document::create([
                'name' => pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME),
                'is_shared' => $request->get('is_shared'),
                'is_protected' => $request->get('is_protected'),
                'path' => $path,
                'document_name' => $document_name,
                'owner_id' => auth()->id(),
                'folder_id' => $folder_id,
                'download_link' => $download_link,
                'document_type_id' => $document_type_id,
                'size' => $request->file('document')->getSize()
            ]);

            $id = $document_create->id;

            if($request['is_shared']){
                $this->toShare($request['id_users'], $id);
            }

            return response()->json([
                "message" => "Document successfully uploaded"
            ]);

        }

        return response()->json([
            "message" => "Error"
        ], 422);
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

        if ($document = auth()->user()->documentsUser()->find($id)){

            $updated = $document->update([
                'name' => $request->name
            ]);

            if($document->is_shared){
                $this->updateToShare($request['id_users'], $document);
            }

            if ($updated)
                return response()->json([
                    'message' => 'The document was successfully updated'
                ], 200);
            else
                return response()->json([
                    'message' => 'The document cannot be updated'
                ], 500);
        }

        return response()->json([
            'status' => 'Error'
        ], 422);
    }


    public function delete(int $id): JsonResponse
    {
        $document_owner = auth()->user()->documentsUser()->find($id);
        if ($document_owner){
            $document_owner->delete();
            return response()->json([
                'status' => 'Document has been placed in the trash'
            ]);
        }
        return response()->json([
            'status' => 'Error'
        ], 422);
    }

    public function display(int $id): JsonResponse|string
    {
        $document = auth()->user()->documentsUser()->find($id);
        if ($document){
            return asset('/storage/' .$document->path);
        }
        return response()->json([
            'status' => 'Error'
        ], 422);
    }

    public function download(int $id): BinaryFileResponse
    {
        $download_link = Document::find($id)->download_link;
        return response()->download($download_link);
    }


    public function toShare(array $id_users, int $id)
    {
        $id_users = array_unique(array_diff($id_users, array(auth()->id())));
        foreach ($id_users as $value){
            $user_permission = new DocumentPermissions;
            $user_permission->user_id = $value;
            $user_permission->document_id = $id;
            $user_permission->save();
        }
    }

    public function updateToShare(array $id_users, Document $document)
    {
        $id_users = array_unique(array_diff($id_users, array(auth()->id())));
        $document->usingDocument()->sync($id_users);
    }

    public function restore(int $id): JsonResponse
    {
        auth()->user()->documentsUser()->onlyTrashed()->findOrFail($id)->restore();
        return response()->json(['status' => 'Document has been restored']);
    }
}
