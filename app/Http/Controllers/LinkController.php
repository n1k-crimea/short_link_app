<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Hashids\Hashids;
use App\Models\Link;

class LinkController extends Controller
{
    private $host;
    private $port;
    private $redirect;

    public function __construct()
    {
        $this->host = config('app.host');
        $this->port = config('app.port');
        if ($this->port)
            $this->redirect = $this->host . ':' . $this->port . config('app.redirect_prefix');
        else
            $this->redirect = $this->host . config('app.redirect_prefix');
    }

    public function process(Request $request)
    {
        $longUrl = rtrim($request->get('long_url'), '/');
        $link = Link::where('long_url', '=', $longUrl)->first();
        if ($link instanceof Link && !is_null($link->short_code))
            return response()->json([
                'short_url' => $this->redirect . $link->short_code,
            ]);
        else {
            $link = Link::create(['long_url' => $longUrl]);
            if ($link instanceof Link) {
                $hashids = new Hashids(env('APP_KEY'), 4);
                $short_code = $hashids->encode($link->id);
                if ($link->update(['short_code' => $short_code]))
                    return response()->json([
                        'short_url' => $this->redirect . $short_code,
                    ]);
                else
                    return response()->json([
                        'error' => 'Произошла ошибка'
                    ]);
            }
        }
        return response()->json([
            'error' => 'Произошла ошибка'
        ]);
    }

    public function redirect(string $short_code)
    {
        $link = Link::where('short_code', '=', $short_code)->first();
        if ($link instanceof Link) {
            header('Location: ' . $link->long_url, true, 301);
            die();
        }
        else
            return response('Редирект не возможен, такая ссылка не обрабатывалась ранее');
    }
}
