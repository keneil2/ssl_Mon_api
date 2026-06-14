<?php 

namespace App\Exception;

use App\Http\Responses\ApiResponse;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ExceptionHandler{
    public static $handlers=[
      AuthenticationException::class => 'handleAuthenticationException',
        AccessDeniedHttpException::class => 'handleAuthenticationException',
        AuthorizationException::class => 'handleAuthorizationException',
        ValidationException::class => 'handleValidationException',
        ModelNotFoundException::class => 'handleNotFoundException',
        NotFoundHttpException::class => 'handleNotFoundException',
        MethodNotAllowedHttpException::class => 'handleMethodNotAllowedException',
        HttpException::class => 'handleHttpException',
        QueryException::class => 'handleQueryException',
    ];
    
    public function handleAuthenticationException(AuthorizationException $e,Request $request):JsonResponse{
      $this->logException($e, 'Authentication failed');
    
      return ApiResponse::error('Authentication required. Please provide valid credentials.',401,[
    "type"=>$this->getExceptionType($e),
]);
    }

    public function handleValidationException(\Illuminate\Validation\ValidationException $e,Request $request):JsonResponse{
   $errors=[];
   foreach ($e->errors() as $field => $messages) {
    foreach ($messages as $message) {
      $errors[] = [
        "field" => $field,
        "message" => $message
        ];
    }
   }
    $this->logException($e, 'Validation failed', ['errors' => $errors]);
    return ApiResponse::error("The provided data is invalid",422,[
        "type"=>$this->getExceptionType($e),
        'validation_errors' => $errors,
    ]);

    }


    public function handleNotFoundException(ModelNotFoundException|NotFoundHttpException $e,
        Request $request):JsonResponse{
        $this->logException($e,"Resource not found");
        $message = $e instanceof ModelNotFoundException ? "The request resource was not found" : "The request endpoint ". $request->getRequestUri()." was not found.";
        return ApiResponse::error($message,404,[
            "type"=>$this->getExceptionType($e),
            
        ]);
        }

        public function handleMethodNotAllowedException(MethodNotAllowedHttpException $e,Request $request):JsonResponse{
            $this->logException($e,"Method not allowed");
            return ApiResponse::error("The ".$request->method()." method is not allowed for this route.",405,[
                
                "allowed_methods"=>$e->getHeaders()["Allow"] ?? "Unknown"
            ]);

        }

        public function handleHttpException(HttpException $e,Request $request):JsonResponse{
            $this->logException($e,"HTTP exception occurred");
            return ApiResponse::error($e->getMessage() ?? "An HTTP Error Occured",$e->getStatusCode(),[
                "type"=>$this->getExceptionType($e),
            ]);
        }
private function getExceptionType($e){
    $className = basename(str_replace('\\','/',get_class($e)));
    return $className;
}


public function handleQueryException(QueryException $e, Request $request): JsonResponse{
    $this->logException($e,'Database query failed', ['sql' => $e->getSql()]);
    $errorCode = $e->errorInfo[1] ?? null;
    switch ($errorCode) {
        case 1451:
            return ApiResponse::error("Connot delete this resource because it is referenced by other records.", 409,['type' => $this->getExceptionType($e)] );
            break;
        case 1062:
            return ApiResponse::error("A record with this information already exist.",409,["type"=>$this->getExceptionType($e)]);
            break;


        default:
         return ApiResponse::error("A database error occurred. Please try again", 500, [
            'type' => $this->getExceptionType($e),
         ]);
            break;
    }
}
private function logException(Throwable $e,string $message,array $context=[]){
   // added try catch incase logging failed the correct response would still go to the client    
try {
       $logContext=array_merge([
        "exception"=> get_class($e),
        "message" => $e->getMessage(),
        'file'=>$e->getFile(),
        "line" => $e->getLine(),
        'url' => request()->fullUrl(),
        "method"=> request()->method(),
        "ip"=>request()->ip() 
    ],$context);
    Log::debug($message,$logContext);
    } catch (Exception $th) {
        return null; 
    }
    
}
    }

