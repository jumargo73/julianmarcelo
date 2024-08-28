<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(){
        $usuarios = User::with('roles')->get();
        return view('users.index', compact(['usuarios']));
    }

    public function create(){
        $roles = Role::all();
        $departments = Department::all(); // Obtener los departamentos

        return view('users.create', compact(['roles', 'departments']));
    }


    public function list()
    {
        $roles = Role::all();
        $cities = City::all(); // Obtener los departamentos       
        return view('city.index', compact(['roles', 'cities'=>DB::table('cities')->paginate(15)]));
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
            'role_id' => 'required|exists:roles,id',
            'status' => 'nullable|boolean',
            'department_id' => 'required|exists:departments,id',
            'city_id' => 'required|exists:cities,id',
            'birth_date' => 'nullable|date',
            'phone' => 'required|string|max:15', // Validación para phone
            'type_document' => 'required|string|max:3', // Validación para type_document
            'document_number' => 'required|string|max:20|unique:users,document_number', // Validación para document_number
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->lastname = $request->lastname;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->status = $request->status;
        $user->city_id = $request->city_id;
        $user->birth_date = $request->birth_date;
        $user->phone = $request->phone; // Asignación del campo phone
        $user->type_document = $request->type_document; // Asignación del campo type_document
        $user->document_number = $request->document_number; // Asignación del campo document_number
        $user->save();

        // Asignar el rol
        $role = Role::find($request->role_id);
        $user->assignRole($role);

        // Redirigir con mensaje de éxito
        return redirect()->route('users.index')->with('success', 'Usuario creado con éxito.');
    }

    public function edit($id){
        $user = User::find($id);
        $roles = Role::all();
        $departments = Department::all();
        return view('users.update', compact(['user', 'roles', 'departments']));
    }

    public function update(Request $request){
        $userId = $request->id;
        $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => 'nullable|string|max:15', // Validación para el teléfono
            'type_document' => 'required|string|max:50', // Validación para el tipo de documento
            'document_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'document_number')->ignore($userId),
            ],
            'role_id' => 'required|exists:roles,id',
            'status' => 'nullable|boolean',
            'department_id' => 'required|exists:departments,id',
            'city_id' => 'required|exists:cities,id',
            'birth_date' => 'nullable|date',
        ]);

        $user = User::findOrFail($userId);
        $user->name = $request->name;
        $user->lastname = $request->lastname;
        $user->email = $request->email;
        $user->phone = $request->phone; // Asignar el teléfono
        $user->type_document = $request->type_document; // Asignar el tipo de documento
        $user->document_number = $request->document_number; // Asignar el número de documento
        $user->status = $request->status;
        $user->city_id = $request->city_id;
        $user->birth_date = $request->birth_date;
        $user->save();

        // Asignar el rol
        $role = Role::findOrFail($request->role_id);
        $user->syncRoles($role); // Usa syncRoles si el rol puede cambiar

        // Redirigir con mensaje de éxito
        return redirect()->route('users.index')->with('success', 'Usuario actualizado con éxito.');
    }

    public function profileEdit($id){
        $user = User::find($id);
        $roles = Role::all();
        $departments = Department::all(); // Obtener los departamentos
        $profileUpdate = true;
        return view('users.update', compact(['user', 'roles', 'profileUpdate', 'departments']));
    }

    public function profileUpdate(Request $request){
        $userId = $request->id;
        $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => 'nullable|string|max:15', // Validación para el teléfono
            'type_document' => 'required|string|max:50', // Validación para el tipo de documento
            'document_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'document_number')->ignore($userId),
            ],
            'department_id' => 'required|exists:departments,id',
            'city_id' => 'required|exists:cities,id',
            'birth_date' => 'nullable|date',
        ]);

        $user = User::findOrFail($userId);
        $user->name = $request->name;
        $user->lastname = $request->lastname;
        $user->email = $request->email;
        $user->phone = $request->phone; // Asignar el teléfono
        $user->type_document = $request->type_document; // Asignar el tipo de documento
        $user->document_number = $request->document_number; // Asignar el número de documento
        $user->city_id = $request->city_id;
        $user->birth_date = $request->birth_date;
        $user->save();

        // Redirigir con mensaje de éxito
        return redirect()->route('profile.edit', ['id' => $userId])->with('success', 'Perfil actualizado con éxito.');
    }

    public function RegisterUsers(){
        $roles = Role::all();
        return view('users.register', compact(['roles']));
    }

    public function RegisterUsersStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->lastname = $request->lastname;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->status = 0; // Default status
        $user->save();

        // Assign default role
        $user->assignRole('user');

        return redirect()->route('users.register')->with('success', 'Usuario Registrado satisfactoriamente');
    }

    public function changePassword(){
        return view('users.changePassword');
    }

    public function changePasswordUpdate(Request $request)
    {
        // Validación de las entradas
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Verificar si la contraseña actual es correcta
        if (!Hash::check($request->old_password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'old_password' => 'La contraseña actual no es correcta.',
            ]);
        }

        // Cambiar la contraseña del usuario
        $user = Auth::user();
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Redireccionar con un mensaje de éxito
        return redirect()->route('profile.changePassword')->with('success', 'Contraseña actualizada correctamente.');
    }

    public function resetPasswordIndex(){
        return view('users.resetPassword');
    }

    public function resetPassword(Request $request)
    {
        // Validate the email address
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        // Send the password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );
        return "prubea";

        // Check if the email was successfully sent
        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        // Handle the case where the email could not be sent
        return back()->withErrors(['email' => __($status)]);
    }

    public function changeProfilePhoto(){
        return view('users.changeProfilePhoto');
    }

    public function changeProfilePhotoUpdate(Request $request)
    {
        // Validar que el archivo subido sea una imagen y que no exceda 2MB
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Obtener el usuario autenticado
        $user = Auth::user();

        // Eliminar la foto de perfil anterior si existe
        if ($user->profile_photo_path) {
            Storage::delete($user->profile_photo_path);
        }

        // Almacenar la nueva foto de perfil
        $path = $request->file('profile_photo')->store('profile_photos', 'public');

        // Actualizar la ruta de la foto de perfil en la base de datos
        $user->profile_photo_path = $path;
        $user->save();

        // Redirigir al usuario de vuelta con un mensaje de éxito
        return redirect()->back()->with('success', 'Foto de perfil actualizada exitosamente.');
    }

}
