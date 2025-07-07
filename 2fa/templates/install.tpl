{assign var="title" value="Installation check"}
{extends file="layout.tpl"}

{block name="content"}
<h2>Installation Check</h2>
<p>This page checks if your server environment is correctly configured for the 2FA system.</p>

<table border="1" cellpadding="10" cellspacing="0">
  <thead>
    <tr>
      <th>Check</th>
      <th>Status</th>
      <th>Explanation</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>/2fa/data is writable</td>
      <td>
        {if $results.data_writable}
          ✅ Passed
        {else}
          ❌ Failed
        {/if}
      </td>
      <td>
        {if $results.data_writable}
          This folder is writable.
        {else}
          Ensure the web server has write permissions for <code>/2fa/data</code>.
          You can run:<br>
          <code>chmod 775 /path/to/2fa/data</code><br>
          and ensure the folder is owned by the web server user.
        {/if}
      </td>
    </tr>

    <tr>
      <td>/2fa/vendor exists</td>
      <td>
        {if $results.vendor_exists}
          ✅ Passed
        {else}
          ❌ Failed
        {/if}
      </td>
      <td>
        {if $results.vendor_exists}
          Composer dependencies are installed.
        {else}
          Run <code>composer install</code> in the <code>/2fa</code> directory to generate this folder.
        {/if}
      </td>
    </tr>

    <tr>
      <td>/2fa/templates_c is writable</td>
      <td>
        {if $results.templates_c_writable}
          ✅ Passed
        {else}
          ❌ Failed
        {/if}
      </td>
      <td>
        {if $results.templates_c_writable}
          This folder is writable.
        {else}
          Smarty needs write access here. Run:<br>
          <code>chmod 775 /path/to/2fa/templates_c</code>
        {/if}
      </td>
    </tr>

    <tr>
      <td>/2fa/public exists</td>
      <td>
        {if $results.public_exists}
          ✅ Passed
        {else}
          ❌ Failed
        {/if}
      </td>
      <td>
        {if $results.public_exists}
          Public assets folder found.
        {else}
          Run <code>composer install</code> to generate the <code>public</code> folder.
        {/if}
      </td>
    </tr>

    <tr>
      <td>/2fa/sessions is writable</td>
      <td>
        {if $results.sessions_writable}
          ✅ Passed
        {else}
          ❌ Failed
        {/if}
      </td>
      <td>
        {if $results.sessions_writable}
          Session storage directory is writable.
        {else}
          Run:<br>
          <code>chmod 775 /path/to/2fa/sessions</code><br>
          or set appropriate ownership.
        {/if}
      </td>
    </tr>
  </tbody>
</table>

{if $results.all_passed}
  <p style="color: green; font-weight: bold; font-size: 18px;">✅ All system checks passed. You are ready to proceed with the installation.</p>
{else}
  <p style="color: red; font-weight: bold; font-size: 18px;">❌ One or more system checks failed. Please correct the issues above and reload this page.</p>
{/if}

{/block}
