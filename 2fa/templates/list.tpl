{assign var="title" value="User List"}
{assign var="show_navbar" value=true}
{extends file="admin_layout.tpl"}

{block name="pagecss"}
.toggle-admin {
    text-decoration: none;
    cursor: pointer;
    display: inline-block;
    padding: 4px;
    border-radius: 4px;
}

.toggle-admin i {
    font-size: 1.2em;
    display: inline-block;
    transition: transform 0.2s ease, background-color 0.2s;
}

.toggle-admin:hover i {
    transform: scale(1.2);
    background-color: rgba(0, 0, 0, 0.05);
    border-radius: 4px;
}
{/block}

{block name="content"}

<h2>2FA Users</h2>

<button type="button" id="adduserbutton" class="btn btn-primary mb-3">
    Add User
</button>

<table id="users-table" class="table table-bordered">
  <thead>
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th>Admin?</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <tr><td colspan="3" class="text-center">Loading...</td></tr>
  </tbody>
</table>


<script>
function bindModalForm(formId, url, method="POST") {
    const form = document.getElementById(formId);
    if (!form) {
        console.warn(`No form found with id "$formId"`);
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);

        fetch(url, {
            method: method,
            body: formData
        })
        .then(res => res.text())
        .then(response => {
            //TODO: response.success
            if (response.includes('<form')) {
                document.getElementById('genericModalBody').innerHTML = response;
                bindEditUserForm(); // rebind after re-rendered form
            } else {
                bootstrap.Modal.getInstance(document.getElementById('genericModal')).hide();
                $(document).trigger('modalFormSuccess', [form.id, response]);
            }
        });
    });
}

$(document).ready(function () {
  $('#users-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: 'users',
      type: 'GET',
      data: {
        _csrf_token: '{$csrf_token}'
      }
    },
    columns: [
      { data: 'name' },
      { data: 'email' },
      { data: 'isadmin' },
      { data: 'actions', orderable: false, searchable: false }
    ]
  });

  $(document).on('click', '.toggle-admin', function (e) {
    e.preventDefault();

    const row = $(this).closest('tr');
    const email = row.data('email');

    $.post('user/toggle-admin', {
      email: email,
      _csrf_token: '{$csrf_token}'
    }, function (res) {
      if (res.success) {
        $('#users-table').DataTable().ajax.reload(null, false);
      } else {
        alert('Failed to toggle admin: ' + (res.error || 'Unknown error'));
      }
    }, 'json');
  });

  $(document).on('click', '#adduserbutton', function (e) {
    e.preventDefault();

    const row = $(this).closest('tr');
    const email = row.data('email');
    openModal('Add User', 'user/add', () => bindModalForm("addUserForm", 'user/add'));
  });

  $(document).on('click', '.edit-user', function (e) {
    e.preventDefault();

    const row = $(this).closest('tr');
    const email = row.data('email');
    openModal('Edit User', 'user/edit?email='+email, () => bindModalForm("editUserForm", 'user/edit'));
  });

  $(document).on('modalFormSuccess', function (e, formId, responseData) {
      if (formId === 'addUserForm' || formId === 'editUserForm') {
          console.log("Add user form submitted successfully!", responseData);
          // You could reload other components, show toast, etc.
          $('#users-table').DataTable().ajax.reload();
      }
  });
});
</script>
{/block}

