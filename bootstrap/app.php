<?php

use App\Http\Responses\ApiResponse;
use Bootstrap\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php'
    )
    ->withMiddleware(function (Middleware $middleware): void {
           $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(
            function (Throwable $e,Request $request){
                $className = get_class($e);
                $handlers = ExceptionHandler::$handlers;
                if(array_key_exists($className,$handlers)){
                   $method = $handlers[$className];
                   $apiHandler = new ExceptionHandler();
                   return $apiHandler->$method($e,$request);
                }
                return ApiResponse::error($e->getMessage() ?? "An Unexpected error occurred.",$e->getCode() ?? 500,[
                    "type"=>$className,
                     'debug' => app()->environment('local', 'testing') ? [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ] : null
                ]);
            }
        );
    })->create();
