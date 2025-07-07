{assign var="title" value="Edit User"}
{extends file="admin_layout.tpl"}

{block name="content"}
<h2>Edit User</h2>

<form method="post" id="editUserForm">
    <input type="hidden" name="_csrf_token" value="{$csrf_token}" />
    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required value="{$name}">
    </div>
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="isAdminSwitch" name="is_admin" value="1" {if $is_admin}checked{/if}>
        <label class="form-check-label" for="isAdminSwitch">Is Admin</label>
    </div>
    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control-plaintext" readonly value="{$email}">
    </div>
    <div class="mb-3">
        <label>Password <small class="text-muted">(Leave blank to keep current)</small></label>
        <input type="password" name="password" class="form-control" placeholder="Enter new password">
    </div>
    <button class="btn btn-primary">Edit User</button>
</form>

{/block}
