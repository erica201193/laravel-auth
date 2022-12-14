<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewPostMail;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /* SLUG */
    private function findBySlug($slug)
    {
        $post = Post::where("slug", $slug)->first();

        if (!$post) {
            abort(404);
        }

        return $post;
    }

    private function generateSlug($text)
    {
        $toReturn = null;
        $counter = 0;

        do {
            // generiamo uno slug partendo dal titolo
            $slug = Str::slug($text);

            // se il counter é maggiore di 0, concateno il suo valore allo slug
            if ($counter > 0) {
                $slug .= "-" . $counter;
            }

            // controllo a db se esiste già uno slug uguale
            $slug_esiste = Post::where("slug", $slug)->first();

            if ($slug_esiste) {
                // se esiste, incremento il contatore per il ciclo successivo
                $counter++;
            } else {
                // Altrimenti salvo lo slug nei dati del nuovo post
                $toReturn = $slug;
            }
        } while ($slug_esiste);

        return $toReturn;
    }

    /* -------------- I N D E X -------------- */

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::orderBy("created_at", "desc")->get();

        return view("admin.posts.index", compact("posts"));
    }

    /* -------------- C R E A T E -------------- */

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view("admin.posts.create");
    }

    /* -------------- S T O R E -------------- */

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validazione
        $validatedData = $request->validate([
            "title" => "required|min:10",
            "content" => "required|min:10",
        ]);

        // Salvataggio a db
        $post = new Post();
        $post->fill($validatedData);

        // Creazione slug
        $post->slug = $this->generateSlug($post->title);

        // Salvataggio
        $post->save();

        // Redirect alla pagina show
        return redirect()->route("admin.posts.show", $post->slug);


        // M A I L S

        // Mail::to($post->user->email)->send(new NewPostMail);
    }

    /* -------------- S H O W -------------- */
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $post = $this->findBySlug($slug);
        return view("admin.posts.show", compact("post"));
    }

    /* -------------- E D I T -------------- */

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($slug)
    {
        $post = $this->findBySlug($slug);

        return view("admin.posts.edit", compact("post"));
    }

    /* -------------- U P D A T E -------------- */

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $validatedData = $request->validate([
            "title" => "required|min:10",
            "content" => "required|min:10"
        ]);
        $post = $this->findBySlug($slug);

        if ($validatedData["title"] !== $post->title) {
            // genero un nuovo slug
            $post->slug = $this->generateSlug($validatedData["title"]);
        }

        $post->update($validatedData);

        return redirect()->route("admin.posts.show", $post->slug);
    }

     /* -------------- D E S T R O Y -------------- */
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        $post = $this->findBySlug($slug);

        $post->delete();

        return redirect()->route("admin.posts.index");
    }
}
