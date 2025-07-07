<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$title|default:'Admin'}</title>
    <link rel="stylesheet" href="{$basePath}/public/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="{$basePath}/public/assets/bootstrap-icons/font/bootstrap-icons.css">
    <script src="{$basePath}/public/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="{$basePath}/public/assets/jquery/jquery.min.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="{$basePath}/public/assets/datatables/css/dataTables.dataTables.min.css">
    <script src="{$basePath}/public/assets/datatables/js/dataTables.min.js"></script>

    <script>
    // Open modal with dynamic content and title
    function openModal(title, contentUrl, onLoaded=null, postData = {}) {
        const modalTitle = document.getElementById('genericModalLabel');
        const modalBody = document.getElementById('genericModalBody');
        modalTitle.textContent = title;
        modalBody.innerHTML = '<p>Loading…</p>';

      if (Object.keys(postData).length > 0) {
          // POST request
          fetch(contentUrl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: new URLSearchParams(postData),
          })
          .then(response => response.text())
          .then(html => {
              modalBody.innerHTML = html;
              const modal = new bootstrap.Modal(document.getElementById('genericModal'));
              modal.show();
          })
          .catch(err => {
              modalBody.innerHTML = `<p class="text-danger">Error loading content</p>`;
          });
      } else {
        // GET request
        fetch(contentUrl)
        .then(res => res.text())
        .then(html => {
            modalBody.innerHTML = html;
            new bootstrap.Modal(document.getElementById('genericModal')).show();
            if (typeof onLoaded === 'function') {
                onLoaded(); // Call callback after content is loaded
            }
        });
      }
    }
    </script>
    <style>
      {block name="pagecss"}{/block}
    </style>
</head>
<body class="p-4">
{if $show_navbar == true}
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 rounded shadow-sm px-3">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h4">{$title|default:'Admin'}</span>
            <div class="d-flex">
                <a href="{$basePath}/admin/logout" class="btn btn-outline-danger btn-sm">Log out</a>
            </div>
        </div>
    </nav>
{/if}

    <!-- Generic Modal -->
    <div class="modal fade" id="genericModal" tabindex="-1" aria-labelledby="genericModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="genericModalLabel">Modal Title</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="genericModalBody">
            <p>Loading...</p>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
    {block name="content"}{/block}
    </div>

</body>
</html>

