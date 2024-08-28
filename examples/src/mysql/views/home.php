<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Migration</title>
</head>
<body>
  <h1>Register</h1>
  <form action="/mysql/setup" method="post">
    <label for="schema">Schema</label>
    <input type="text" name="schema" id="schema" style="margin-bottom: 1rem;">
    <br />
    <label for="direction">Direction</label>
    <input type="text" name="direction" id="direction" style="margin-bottom: 1rem;">
    <br />
    <label for="step">Step</label>
    <input type="text" name="step" id="step" style="margin-bottom: 1rem;">
    <br />
    <label for="all">All</label>
    <input type="text" name="all" id="all" style="margin-bottom: 1rem;">
    <br />
    <button type="submit">Submit</button>
  </form>
</body>
</html>