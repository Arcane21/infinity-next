<?php namespace App\Http\Requests;

use App\Ban;
use App\Board;
use App\Services\UserManager;

use Auth;
use View;

class PostRequest extends Request {
	
	const VIEW_BANNED = "errors.banned";
	
	/**
	 * Input items that should not be returned when reloading the page.
	 *
	 * @var array
	 */
	protected $dontFlash = ['password', 'password_confirmation', 'captcha'];
	
	/**
	 * Fetches the user and our board config.
	 *
	 * @return void
	 */
	public function __construct(UserManager $manager)
	{
		$this->user = $manager->user;
	}
	
	/**
	 * Get all form input.
	 *
	 * @return array
	 */
	public function all()
	{
		$input = parent::all();
		
		if (isset($input['files']) && is_array($input['files']))
		{
			// Having an [null] file array passes validation.
			$input['files'] = array_filter($input['files']);
		}
		
		if (isset($input['capcode']) && $input['capcode'])
		{
			$user = $this->user;
			
			if ($user && !$user->isAnonymous())
			{
				$role = $user->roles->where('role_id', $input['capcode'])->first();
				
				if ($role && $role->capcode != "")
				{
					$input['capcode_id'] = (int) $role->role_id;
					$input['author']     = $user->username;
				}
			}
			else
			{
				unset($input['capcode']);
			}
		}
		
		return $input;
	}
	
	/**
	 * Returns if the client has access to this form.
	 *
	 * @return boolean
	 */
	public function authorize()
	{
		return !Ban::isBanned($this->ip(), $this->board);
	}
	
	/**
	 * Returns validation rules for this request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$board = $this->board;
		$user  = $this->user;
		$rules = [
			// Nothing, by default.
			// Post options are contingent on board settings and user permissions.
		];
		
		// Modify the validation rules based on what we've been supplied.
		if ($board && $user)
		{
			$rules = [
				'body' => [ "max:" . $board->getSetting('postMaxLength', 65534) ],
			];
			
			if (!$board->canAttach($user))
			{
				$rules['body'][]  = "required";
				$rules['files'][] = "array";
				$rules['files'][] = "max:0";
			}
			else
			{
				$attachmentsMax = $board->getSetting('attachmentsMax', 1);
				
				$rules['body'][]  = "required_without:files";
				$rules['files'][] = "array";
				$rules['files'][] = "min:1";
				$rules['files'][] = "max:{$attachmentsMax}";
				
				// Create an additional rule for each possible file.
				for ($attachment = 0; $attachment < $attachmentsMax; ++$attachment)
				{
					$rules["files.{$attachment}"] = [
						"mimes:jpeg,gif,png",
						"between:0,8000",// . $controller->option('attachmentFilesize'),
					];
				}
			}
		}
		
		return $rules;
	}
	
	/**
	 * Get the response for a forbidden operation.
	 *
	 * @param array $errors
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function response(array $errors)
	{
		$redirectURL = $this->getRedirectUrl();
		
		return redirect($redirectURL)
			->withInput($this->except($this->dontFlash))
			->withErrors($errors, $this->errorBag);
	}
	/**
	 * Validate the class instance.
	 * This overrides the default invocation to provide additional rules after the controller is setup.
	 *
	 * @return void
	 */
	public function validate()
	{
		$board = $this->board;
		$user  = $this->user;
		
		if (!$board || !$user)
		{
			return parent::validate();
		}
		
		$validator = $this->getValidatorInstance();
		
		$validator->sometimes('captcha', "required|captcha", function($input) use ($board) {
			return !$board->canPostWithoutCaptcha($this->user);
		});
		
		if (!$validator->passes())
		{
			$this->failedValidation($validator);
		}
	}
}