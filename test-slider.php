<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Slider Test</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { padding: 20px; font-family: sans-serif; background: #f0f0f0; }
    .info { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    .box { width: 100%; max-width: 800px; height: 300px; position: relative; overflow: hidden; border: 3px solid red; margin-bottom: 20px; }
  </style>
</head>
<body>

<div class="info">
  <h1>Slider Diagnostic</h1>
  <p><strong>Below this box:</strong> a test with 3 slides using the SAME CSS classes as your homepage.</p>
  <p><strong>If this works:</strong> Your CSS is fine, the problem is in index.php.</p>
  <p><strong>If this doesn't work:</strong> Your CSS is broken.</p>
</div>

<div class="box" id="testHero">
  <div class="hero__slides" style="position:absolute;inset:0;">
    <div class="hero__slide hero__slide--active" style="background-image:url('https://images.pexels.com/photos/8961065/pexels-photo-8961065.jpeg?auto=compress&cs=tinysrgb&w=800');opacity:1;"></div>
    <div class="hero__slide" style="background-image:url('https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?auto=format&fit=crop&w=800&q=80');"></div>
    <div class="hero__slide" style="background-image:url('https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=800&q=80');"></div>
  </div>
</div>

<div class="info">
  <h2>Now let's test rotation</h2>
  <p>If you can see image 1 above, we'll rotate to slide 2 in 2 seconds, then slide 3 in 4 seconds.</p>
  <button onclick="location.reload()">Reload</button>
</div>

<script>
window.addEventListener('load', () => {
  console.log('Slider test loaded');
  const slides = document.querySelectorAll('#testHero .hero__slide');
  console.log('Found slides:', slides.length);

  slides.forEach((s, i) => {
    const computed = window.getComputedStyle(s);
    console.log('Slide', i, '- opacity:', computed.opacity, 'size:', computed.width, 'x', computed.height);
  });

  /* Rotate every 2 seconds */
  let current = 0;
  setInterval(() => {
    current = (current + 1) % slides.length;
    slides.forEach((s, i) => {
      s.style.opacity = (i === current) ? '1' : '0';
    });
    console.log('Now showing slide', current);
  }, 2000);
});
</script>

</body>
</html>