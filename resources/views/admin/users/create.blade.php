<x-layouts.app>

<x-slot:heading>
    Users
</x-slot>

<x-form.input name="username" label="Username" placeholder="Enter your username" required />
<x-form.textarea name="bio" label="Bio" placeholder="Tell us about yourself" />
<x-form.select name="gender" label="Gender" :options="['male' => 'Male', 'female' => 'Female']" />
<x-form.checkbox name="terms" label="I agree to the terms and conditions" required />
<x-form.file name="profile_picture" label="Upload Profile Picture" required />


</x-layouts.app>