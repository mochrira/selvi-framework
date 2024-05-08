<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <style>
        .box {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: stretch;
            justify-content: center;
            flex-direction: column;
            background-color: #EFEFEF;
        }

        .box-inner {
            width: 100%;
            max-width: 800px;
            display: block;
            margin: auto;
            padding: 1.5rem;
            background-color: #B00;
            color: white;
            border-radius: .25rem;
            font-family: Arial, Helvetica, sans-serif;
        }

        h1 {
            font-weight: 500;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            margin-bottom: 1rem;
        }

        div.message {
            margin-bottom: 1.5rem;
        }

        code {
            color: #EFEFEF;
            background-color: #333;
            border-radius: .25rem;
            padding: 1rem 1.5rem;
            display: block;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="box-inner">
            <h1><?php echo $codeString; ?></h1>
            <div class="message"><?php echo $message; ?></div>
            <code>
                <?php echo $query; ?>
            </code>

            <code>
                <?php echo $backtrace; ?>
            </code>
        </div>
    </div>
</body>
</html>