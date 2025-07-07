{assign var="title" value="Add User"}
{extends file="admin_layout.tpl"}

{block name="content"}
<h2>Add User</h2>

<form method="post" id="addUserForm">
    <input type="hidden" name="_csrf_token" value="{$csrf_token}" />
    <input type="hidden" name="action" value="add" />
    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <button class="btn btn-primary">Create User</button>
</form>

{/block}
