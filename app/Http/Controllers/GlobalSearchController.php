<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseApi;
use App\Http\Resources\SearchesCollection;
use App\Models\IsiPeraturan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GlobalSearchController extends Controller
{
    const BUFFER = 120;

    /**
     * Display a listing of the search resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        try {
            $request->validate([
                'search_content' => 'required|min:3',
                'category_id' => 'required',
            ]);

            // 1.  using builder join
            // $search = IsiPeraturan::search($request->search_content)
            //     ->query(function ($builder) use ($prefix, $request) {
            //         $builder->join($prefix . 'peraturan', $prefix . 'contents.peraturan_id', '=', $prefix . 'peraturan.id');
            // $builder->where($prefix . 'peraturan.category_id', $request->category_id);
            // })->orderBy($prefix . 'contents.order')->get();

            // 2.  using builder join instance searchable trait on model
            $conditions = [
                'peraturan.category_id' => $request->category_id,
                'contents.kelompok' => 'menetapkan',
            ];

            $search = IsiPeraturan::search($request->search_content, null, $conditions)->get();

            return new SearchesCollection($search);

            // 3.  using callback
            // $isi_peraturan = IsiPeraturan::search($request->search_content, function ($query) use ($request) {
            //     if ($request->category_id !== "") {
            //         $query->whereHas('peraturan', function ($q) use ($request) {
            //             $q->where('category_id', $request->category_id);
            //         })->where('kelompok', 'menetapkan')->orderBy('order');
            //     }
            // })->get();

            // return new SearchesCollection($isi_peraturan);

        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

    public function globalSearch(Request $request)
    {
        $keyword = $request->text;

        // just for demonstration, you can exclude models from the searches here
        // $toExclude = [Comment::class];
        $toExclude = [
            Peraturan::class,
            // IsiPeraturan::class,
        ];

        // getting all the model files from the model folder
        $files = \Illuminate\Support\Facades\File::allFiles(app()->basePath() . '/app/Models');

        $results = collect($files)->map(function (\Symfony\Component\Finder\SplFileInfo$file) {
            $filename = $file->getRelativePathname();
            // assume model name is equal to file name
            // making sure it is a php file
            if (substr($filename, -4) !== '.php') {
                return null;
            }
            // removing .php
            return substr($filename, 0, -4);

        })->filter(function (?string $classname) use ($toExclude) {

            if ($classname === null) {
                return false;
            }

            // using reflection class to obtain class info dynamically
            $reflection = new \ReflectionClass($this->modelNamespacePrefix() . $classname);
            // making sure the class extended eloquent model
            $isModel = $reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class);
            // making sure the model implemented the searchable trait
            $searchable = $reflection->hasMethod('search');
            // filter model that has the searchable trait and not in exclude array
            $getTrueModel = $isModel && $searchable && !in_array($reflection->getName(), $toExclude, true);
            return $getTrueModel;

        })->map(function ($classname) use ($keyword) {
            // for each class, call the search function
            // to create the `match` attribute, we need to join the value of all the searchable fields in
            // our model, ie all the fields defined in our 'toSearchableArray' model method
            // We make use of the SEARCHABLE_FIELDS constant in our model
            // we dont want id in the match, so we filter it out.

            $model = app($this->modelNamespacePrefix() . $classname);
            $fields = array_filter($model::SEARCHABLE_FIELD, fn($field) => $field !== 'id');

            // $resultsSearchx = $model::search($keyword)->get();
            $resultsSearch = $model::search($keyword)->get()->map(function ($modelRecord) use ($fields, $keyword, $classname) {
                // Our goal here: to add these 3 attributes to each of our search result:
                // a. `match` -- the match found in our model records
                $fieldsData = $modelRecord->only($fields);
                $serializedValue = collect($fieldsData)->join(' ');
                $searchPos = strpos(strtolower($serializedValue), strtolower($keyword));

                if ($searchPos !== false) {
                    $start = $searchPos - self::BUFFER;
                    $start = $start < 0 ? 0 : $start;
                    $length = strlen($keyword) + 2 * 10;
                    $sliced = substr($serializedValue, $start, $length);

                    $addPrefix = $start > 0;
                    $addPostfix = ($start + $length) < strlen($serializedValue);

                    $sliced = $addPrefix ? '...' . $sliced : $sliced;
                    $sliced = $addPostfix ? $sliced . '...' : $sliced;
                }

                // b. `model` -- the related model name
                $modelRecord->setAttribute('match', $sliced ?? substr($serializedValue, 0, 2 * self::BUFFER) . '....');
                $modelRecord->setAttribute('model', $classname);
                // c. `view_link` -- the URL for the user to navigate in the frontend to view the resource
                $modelRecord->setAttribute('view_link', $this->resolveModelViewLink($modelRecord));
                // $modelRecord->with('contents')->searchable();

                return $modelRecord;
            });

            return $resultsSearch;

        })->flatten(1);

        // return $results;

        // finaly combine all search result together with resoures and send back as response
        // return ContentResource::collection($results);
        return new SearchesCollection($results);
    }

    /** A helper function to generate the model namespace
     * @return string
     */
    private function modelNamespacePrefix()
    {
        return app()->getNamespace() . 'Models\\';
    }

    private function resolveModelViewLink(Model $model)
    {
        //to return a url like: /{model-name}/{model-id}
        //eg. for post : posts/1
        $mapping = [
            \App\Models\Peraturan::class => 'peraturan/views/{$id}',
        ];
        //get the fully qualified class name of model
        $modelClass = get_class($model);
        //check if class has $mapping entry, if yes, use that url pattern
        if (Arr::has($mapping, $modelClass)) {
            return URL::to(str_replace('{$id}', $model->slug, $mapping[$modelClass]));
        }
        //otherwise, use the default convention
        // convert the class name to kebab case
        $modelName = Str::plural(Arr::last(explode('\\', $modelClass)));
        $modelName = Str::kebab($modelName);

        return URL::to('/' . $modelName . '/' . $model->id);
    }

}
