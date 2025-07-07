<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{$title|default:'2FA Login'}</title>
    <link rel="stylesheet" href="{$basePath}/public/assets/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        /* Reset and base */
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 2rem 3rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgb(0 0 0 / 0.1);
            max-width: 380px;
            width: 100%;
        }
        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #222;
        }
        form label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        form input[type="password"],
        form input[type="email"],
        form input[type="text"] {
            width: 100%;
            padding: 0.45rem 0.7rem;
            margin-bottom: 1.2rem;
            border: 1.8px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        form input[type="password"]:focus,
        form input[type="email"]:focus,
        form input[type="text"]:focus {
            border-color: #3f51b5;
            outline: none;
        }
        button {
            width: 100%;
            padding: 0.6rem 0;
            font-size: 1rem;
            font-weight: 700;
            background-color: #3f51b5;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #303f9f;
        }
        .error-message {
            background: #ffe6e6;
            color: #d32f2f;
            border: 1px solid #d32f2f;
            padding: 0.6rem 1rem;
            margin-bottom: 1.2rem;
            border-radius: 4px;
            font-weight: 600;
            text-align: center;
        }
        #toast {
              visibility: hidden;
              min-width: 200px;
              background-color: #333;
              color: #fff;
              text-align: center;
              border-radius: 8px;
              padding: 0.75em 1em;
              position: fixed;
              z-index: 9999;
              bottom: 30px;
              left: 50%;
              transform: translateX(-50%);
              font-size: 0.9em;
              opacity: 0;
              transition: opacity 0.3s ease, visibility 0.3s;
        }

        #toast.show {
          visibility: visible;
          opacity: 1;
        }
    {block name="pagecss"}{/block}
    </style>
</head>
<body>
    <div class="login-container">
    {block name="content"}{/block}
    </div>
<div id="toast">Passphrase copied to clipboard!</div>
</body>
</html>

