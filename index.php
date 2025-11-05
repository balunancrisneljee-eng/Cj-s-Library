<?php

$library = [
    "Fiction" => [
        "Nature Fiction" => [
            "The Overstory",
            "The River Why",
            "The Snow Leopard"
        ],
        "Adventure Fiction" => [
            "The Lost City of Z",
            "Tracks",
            "The Hobbit"
        ]
    ],
    "Classics" => [
        "Nature Classics" => [
            "Walden",
            "Pilgrim at Tinker Creek",
            "Silent Spring"
        ],
        "Exploration Tales" => [
            "Into the Wild",
            "Into Thin Air"
        ]
    ],
    "Modern Series" => [
        "Nature & Travel" => [
            "Braiding Sweetgrass",
            "A Walk in the Woods",
            "The Hidden Life of Trees"
        ]
    ]
];

// -----------------------------
// Hash Table (Book Info)

// -----------------------------
$bookInfo = [
    // Fiction – Nature Fiction
    "The Overstory" => ["author" => "Richard Powers", "year" => 2018, "genre" => "Eco-fiction", "cover" => "default.jpg"],
    "The River Why" => ["author" => "David James Duncan", "year" => 1983, "genre" => "Environmental Fiction", "cover" => "default.jpg"],
    "The Snow Leopard" => ["author" => "Peter Matthiessen", "year" => 1978, "genre" => "Nature Travel", "cover" => "default.jpg"],

    // Fiction – Adventure Fiction
    "The Lost City of Z" => ["author" => "David Grann", "year" => 2009, "genre" => "Exploration Nonfiction", "cover" => "lostc.jpg"],
    "Tracks" => ["author" => "Robyn Davidson", "year" => 1980, "genre" => "Travel Memoir", "cover" => "default.jpg"],
    "The Hobbit" => ["author" => "J. R. R. Tolkien", "year" => 1937, "genre" => "Fantasy Adventure", "cover" => "hobbit.jpg"],

    // Classics – Nature Classics
    "Walden" => ["author" => "Henry David Thoreau", "year" => 1854, "genre" => "Philosophical Nature", "cover" => "default.jpg"],
    "Pilgrim at Tinker Creek" => ["author" => "Annie Dillard", "year" => 1974, "genre" => "Nature Writing", "cover" => "pilgrim.jpg"],
    "Silent Spring" => ["author" => "Rachel Carson", "year" => 1962, "genre" => "Environmental Science", "cover" => "silent.jpg"],

    // Classics – Exploration Tales
    "Into the Wild" => ["author" => "Jon Krakauer", "year" => 1996, "genre" => "Travel / Biography", "cover" => "wild.jpg"],
    "Into Thin Air" => ["author" => "Jon Krakauer", "year" => 1997, "genre" => "Mountaineering Memoir", "cover" => "thin.jpg"],

    // Modern Series – Nature & Travel
    "Braiding Sweetgrass" => ["author" => "Robin Wall Kimmerer", "year" => 2013, "genre" => "Nature / Indigenous Wisdom", "cover" => "sweetgrass.jpg"],
    "A Walk in the Woods" => ["author" => "Bill Bryson", "year" => 1998, "genre" => "Travel / Humor", "cover" => "walk.jpg"],
    "The Hidden Life of Trees" => ["author" => "Peter Wohlleben", "year" => 2015, "genre" => "Popular Science", "cover" => "hidden.jpg"]
];

function getBookInfo($title, $bookInfo) {
    return $bookInfo[$title] ?? null;
}

// -----------------------------
// Part III: Binary Search Tree
// -----------------------------
class Node {
    public $data;
    public $left;
    public $right;
    public function __construct($data) {
        $this->data = $data;
        $this->left = $this->right = null;
    }
}
class BST {
    public $root = null;
    function insert($data) { $this->root = $this->_insert($this->root, $data); }
    private function _insert($n, $d) {
        if (!$n) return new Node($d);
        // case-insensitive comparison
        if (strcasecmp($d, $n->data) < 0) $n->left = $this->_insert($n->left, $d);
        elseif (strcasecmp($d, $n->data) > 0) $n->right = $this->_insert($n->right, $d);
        return $n;
    }
    function search($data) { return $this->_search($this->root, $data); }
    private function _search($n, $d) {
        if (!$n) return false;
        $cmp = strcasecmp($d, $n->data);
        if ($cmp == 0) return true;
        return $cmp < 0 ? $this->_search($n->left, $d) : $this->_search($n->right, $d);
    }
    function inorder(&$res, $n) {
        if (!$n) return;
        $this->inorder($res, $n->left);
        $res[] = $n->data;
        $this->inorder($res, $n->right);
    }
}

// -----------------------------
// Utility: collect all titles recursively
// -----------------------------
function collectTitles($lib) {
    $titles = [];
    foreach ($lib as $k => $v) {
        if (is_array($v)) {
            // if the array contains only strings -> list of books
            $isBookList = true;
            foreach ($v as $sub) if (is_array($sub)) { $isBookList = false; break; }
            if ($isBookList) {
                foreach ($v as $book) $titles[] = $book;
            } else {
                // deeper categories
                $titles = array_merge($titles, collectTitles($v));
            }
        } else {
            // not expected in this structure, but handle anyway
            $titles[] = $v;
        }
    }
    return $titles;
}

// Build list and BST
$allTitles = collectTitles($library);
$bst = new BST();
foreach ($allTitles as $t) $bst->insert($t);
$alpha = [];
$bst->inorder($alpha, $bst->root);

// -----------------------------
// Simple routing: ?page=home|about|books|contacts
// -----------------------------
$page = $_GET['page'] ?? 'home';

// -----------------------------
// Handle search (simple GET form on home)
// -----------------------------
$searchQuery = trim($_GET['q'] ?? '');
$searchResults = [];
$searchExact = false;
if ($searchQuery !== '') {
    // exact title via BST (case-insensitive)
    $searchExact = $bst->search($searchQuery);
    // partial matches (case-insensitive substring)
    foreach ($allTitles as $t) {
        if (stripos($t, $searchQuery) !== false) $searchResults[] = $t;
    }
}

// -----------------------------
// Handle contact form POST
// -----------------------------
$contactMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($page === 'contacts' || ($_POST['action'] ?? '') === 'contact')) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($name === '' || $email === '' || $message === '') {
        $contactMsg = "Please fill all fields.";
    } else {
        $line = "[" . date("Y-m-d H:i:s") . "] Name: $name | Email: $email | Message: " . str_replace(["\r","\n"], ['',' '], $message) . PHP_EOL;
        // append to contacts.txt (ensure writable)
        @file_put_contents(__DIR__ . '/contacts.txt', $line, FILE_APPEND | LOCK_EX);
        $contactMsg = "Thanks $name! Your message has been received.";
    }
}

// -----------------------------
// HTML / UI Output
// ----------------------------- ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CJ's Library</title>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
/* Dark Forest — Sidebar Bookshelf (animated) */

/* Palette */
:root{
  --bg:#07120f;
  --panel:#0c2119;
  --muted:#9fb19c;
  --accent:#cfe7be;
  --wood:#5a3826;
  --leaf:#2f6b3f;
  --glass: rgba(255,255,255,0.03);
  --card-shadow: rgba(0,0,0,0.7);
  --text:#e9f5ea;
  --trans-dur: 240ms;
}
*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;
  font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  background:
    radial-gradient(800px 500px at 10% 8%, rgba(47,107,63,0.08), transparent 8%),
    linear-gradient(180deg,#04120c 0%, #07120f 60%);
  color:var(--text);
  -webkit-font-smoothing:antialiased;
  line-height:1.45;
}

/* Layout wrapper */
.wrapper{
  max-width:1200px;
  margin:28px auto;
  padding:18px;
  display:grid;
  grid-template-columns: 300px 1fr;
  gap:22px;
  align-items:start;
}

/* Bookshelf Sidebar */
.bookshelf{
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
  border-radius:12px;
  padding:16px;
  box-shadow: 0 18px 50px var(--card-shadow);
  border:1px solid rgba(255,255,255,0.03);
  position:sticky;
  top:24px;
  height:calc(100vh - 56px);
  display:flex;
  flex-direction:column;
  gap:12px;
  overflow:auto;
}

/* Wooden header accent to look like a shelf */
.shelf-top{
  border-radius:8px;
  padding:10px;
  display:flex;
  gap:10px;
  align-items:center;
  color:var(--accent);
  box-shadow: inset 0 -8px 18px rgba(0,0,0,0.35);
}
.logo{
  width:54px;height:54px;border-radius:8px;
  background: linear-gradient(180deg,#2f5a3f,#24432f);
  display:flex;align-items:center;justify-content:center;
  font-weight:900;color:var(--accent);font-size:20px;
  border:1px solid rgba(255,255,255,0.04);
}
.brand h1{ margin:0; font-size:18px; line-height:1; font-family: "Libre Baskerville", serif; }
.brand p{ margin:0; font-size:12px; color:var(--muted); }

/* Navigation shelf (vertical) */
.nav-shelf{
  margin-top:6px;
  padding:10px;
  border-radius:10px;
  background: linear-gradient(180deg, rgba(255,255,255,0.01), transparent);
  border:1px solid rgba(255,255,255,0.02);
}
.nav-shelf a{
  display:block;
  text-decoration:none;
  color:var(--muted);
  padding:10px 12px;
  border-radius:8px;
  margin-bottom:6px;
  font-weight:700;
  transition: all var(--trans-dur) ease;
}
.nav-shelf a:hover{
  transform: translateX(6px);
  color:var(--text);
  background: rgba(255,255,255,0.02);
}
.nav-shelf a.active{
  color:var(--accent);
  background: linear-gradient(90deg, rgba(47,107,63,0.12), rgba(47,107,63,0.06));
  box-shadow: 0 8px 24px rgba(20,40,20,0.25);
  transform: translateX(2px);
}

/* Categories panel (collapsible feel) */
.categories-panel{
  margin-top:6px;
  padding:8px;
  border-radius:10px;
  background: linear-gradient(180deg, rgba(255,255,255,0.01), transparent);
  border:1px solid rgba(255,255,255,0.02);
}
.categories-panel strong{ display:block; margin-bottom:8px; color:var(--accent); }
.cat{ font-weight:700; margin-top:6px; color:var(--text); font-size:14px; padding:3px 0; }
.cat-list{ margin-left:8px; margin-top:6px; max-height:180px; overflow:auto; }
.cat-list a{ display:block; text-decoration:none; color:var(--muted); padding:6px 8px; border-radius:8px; font-weight:600; transition: all var(--trans-dur) ease; }
.cat-list a:hover{ color:var(--text); transform:translateX(6px); }

/* Main column */
.main{
  min-height:60vh;
}

/* Hero */
.hero{
  background: linear-gradient(180deg, rgba(47,107,63,0.06), transparent 40%), url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="500" height="120"><g fill="%232f6b3f" opacity="0.03"><circle cx="40" cy="30" r="20"/><circle cx="140" cy="40" r="35"/><circle cx="320" cy="20" r="44"/></g></svg>') no-repeat 96% 6%;
  border-radius:12px;
  padding:20px;
  display:flex;
  flex-direction:column;
  gap:12px;
  margin-bottom:18px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.6);
  border:1px solid rgba(255,255,255,0.02);
  transition: transform var(--trans-dur) ease, box-shadow var(--trans-dur) ease;
}
.hero:hover{ transform: translateY(-6px); box-shadow: 0 30px 80px rgba(0,0,0,0.7); }
.hero h2{ margin:0; font-size:26px; color:var(--accent); font-family: "Libre Baskerville", serif; }
.hero p{ margin:0; color:var(--muted) }

/* Search */
.search-box{
  width:100%;
  display:flex;
  gap:8px;
  margin-top:8px;
}
.search-box input{
  flex:1; padding:12px; border-radius:10px; border:1px solid rgba(255,255,255,0.03); background:transparent; color:var(--text);
  transition: box-shadow var(--trans-dur) ease, transform var(--trans-dur) ease;
}
.search-box input:focus{ box-shadow: 0 8px 24px rgba(47,107,63,0.12); transform: translateY(-2px); outline:none; }
.search-box button{
  padding:12px 16px; border-radius:10px; border:0; font-weight:700; background: linear-gradient(180deg,#3a6b3f,#274b31); color:var(--accent); cursor:pointer;
  box-shadow: 0 8px 20px rgba(0,0,0,0.5);
}

/* Grid & Card */
.grid{ display:grid; grid-template-columns: repeat(auto-fill,minmax(180px,1fr)); gap:18px; }
.book-card{
  background: linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.00));
  border:1px solid rgba(255,255,255,0.03);
  border-radius:12px; overflow:hidden;
  box-shadow: 0 14px 40px rgba(0,0,0,0.6);
  transition: transform 300ms cubic-bezier(.2,.9,.3,1), box-shadow 300ms ease;
  padding:10px;
  display:flex; flex-direction:column; align-items:center; text-align:center;
}
.book-card:hover{ transform: translateY(-10px) rotate(-0.2deg); box-shadow: 0 30px 70px rgba(0,0,0,0.75); }
.book-cover{ width:100%; }
.book-cover img{ width:100%; height:220px; object-fit:cover; border-radius:8px; display:block; box-shadow: inset 0 -8px 26px rgba(0,0,0,0.28); transition: transform var(--trans-dur) ease; }
.book-card:hover .book-cover img{ transform: scale(1.03); }
.book-title{ margin-top:10px; font-weight:700; color:var(--accent); font-size:14px; text-decoration:none; }
.book-title a{ color:inherit; text-decoration:none; transition: color var(--trans-dur) ease; }
.book-title a:hover{ color:var(--muted) }
.book-author{ font-size:13px; color:var(--muted); margin-top:6px; }

/* Footer */
.footer{ margin:28px 0 40px; text-align:center; color:var(--muted); font-size:13px; }

/* Modal */
.modal{ display:none; position:fixed; inset:0; background: rgba(2,6,4,0.6); align-items:center; justify-content:center; z-index:1200; padding:20px; }
.modal-content{
  background: linear-gradient(180deg,#0f2418,#0b1a12);
  border-radius:12px;
  padding:22px;
  width:100%;
  max-width:620px;
  box-shadow: 0 32px 80px rgba(0,0,0,0.8);
  position:relative;
  text-align:center;
  border:1px solid rgba(255,255,255,0.03);
  transform: translateY(12px);
  transition: transform 220ms ease, opacity 220ms ease;
}
.modal[aria-hidden="false"] .modal-content{ transform: translateY(0); opacity:1; }
.modal-content img{ width:260px; height:auto; border-radius:8px; display:block; margin:10px auto; border:1px solid rgba(255,255,255,0.03) }
.close{ position:absolute; right:12px; top:8px; font-size:22px; cursor:pointer; color:var(--muted) }

/* Responsive */
@media (max-width:980px){
  .wrapper{ grid-template-columns: 1fr; padding:14px; }
  .bookshelf{ position:relative; height:auto; order:2; }
  .main{ order:1; }
  .book-cover img{ height:160px; }
  .grid{ grid-template-columns: repeat(auto-fill,minmax(140px,1fr)); }
}
</style>
</head>
<body>
<div class="wrapper" role="document">

  <!-- Sidebar / Bookshelf -->
  <aside class="bookshelf" role="complementary" aria-label="Sidebar navigation and categories">
    <div class="shelf-top" aria-hidden="false">
      <div class="logo">CJ</div>
      <div class="brand">
        <h1>CJ's Library</h1>
      </div>
    </div>

    <!-- Navigation (acts like wooden shelf labels) -->
    <nav class="nav-shelf" aria-label="Main navigation">
      <a href="?page=home" class="<?php echo $page==='home'?'active':''; ?>">Home</a>
      <a href="?page=books" class="<?php echo $page==='books'?'active':''; ?>">Books</a>
      <a href="?page=about" class="<?php echo $page==='about'?'active':''; ?>">About</a>
      <a href="?page=contacts" class="<?php echo $page==='contacts'?'active':''; ?>">Contact</a>
    </nav>

    <!-- Categories -->
    <div class="categories-panel" id="categoriesPanel" aria-live="polite">
      <strong>Categories</strong>
      <?php
      // recursion display helper (kept as before)
      if (!function_exists('displayLibrary')) {
          function displayLibrary($library, $indent = 0) {
              foreach ($library as $key => $value) {
                  if (is_array($value)) {
                      // detect if array contains only string books
                      $isBookList = true;
                      foreach ($value as $sub) if (is_array($sub)) { $isBookList = false; break; }
                      echo "<div class='cat' style='margin-top:8px;padding-left:".($indent*6)."px'>" . htmlspecialchars($key) . "</div>";
                      if ($isBookList) {
                          echo "<div class='cat-list' style='padding-left:".(($indent+1)*8)."px'>";
                          foreach ($value as $book) {
                              echo "<a href='?page=books' class='open-modal' data-book='" . htmlspecialchars($book) . "'>" . htmlspecialchars($book) . "</a>";
                          }
                          echo "</div>";
                      } else {
                          displayLibrary($value, $indent + 1);
                      }
                  }
              }
          }
      }
      displayLibrary($library);
      ?>
    <div style="margin-top:auto; font-size:13px; color:var(--muted);">
      &copy; <?php echo date('Y'); ?> CJ's Library
    </div>
  </aside>

  <!-- Main content -->
  <main class="main" role="main">

    <!-- Hero -->
    <section class="hero" role="region" aria-label="Hero">
      <h2>Welcome to CJ's Dark Forest</h2>
      
      <!-- Search (only used on home as before) -->
      <div class="search-box" style="max-width:820px;">
        <form method="get" action="" style="display:flex;width:100%;">
          <input type="hidden" name="page" value="home">
          <input name="q" type="text" placeholder="Search books by title" value="<?php echo htmlspecialchars($searchQuery); ?>" aria-label="Search books by title">
          <button type="submit" aria-label="Search">Search</button>
        </form>
      </div>
    </section>

    <!-- Dynamic content area (home/books/about/contacts) -->
    <?php if ($page === 'home'): ?>

      <div style="margin-top:8px; color:var(--muted)">Featured books</div>
      <div class="grid" style="margin-top:12px;">
        <?php foreach (array_slice($alpha, 0, 9) as $t):
          $info = getBookInfo($t, $bookInfo);
          $img = "covers/" . ($info["cover"] ?? "default.jpg");
        ?>
        <div class="book-card">
          <div class="book-cover"><img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($t); ?> cover"></div>
          <div class="book-title"><a href="#" class="open-modal" data-book="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></a></div>
          <div class="book-author"><?php echo htmlspecialchars($info["author"] ?? "Unknown"); ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if ($searchQuery !== ''): ?>
        <div style="margin-top:18px">
          <strong>Search results for "<?php echo htmlspecialchars($searchQuery); ?>"</strong>
          <div style="margin-top:6px;color:var(--muted)">Exact title found via BST: <?php echo $searchExact ? '<strong>Yes</strong>' : '<strong>No</strong>'; ?></div>

          <?php if (count($searchResults) === 0): ?>
            <div style="margin-top:8px;color:var(--muted)">No matches found.</div>
          <?php else: ?>
            <div class="grid" style="margin-top:12px">
              <?php foreach ($searchResults as $t):
                $info = getBookInfo($t, $bookInfo);
                $img = "covers/" . ($info["cover"] ?? "default.jpg");
              ?>
              <div class="book-card">
                <div class="book-cover"><img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($t); ?> cover"></div>
                <div class="book-title"><a href="#" class="open-modal" data-book="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></a></div>
                <div class="book-author"><?php echo htmlspecialchars($info["author"] ?? "Unknown"); ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php elseif ($page === 'books'): ?>

      <div style="margin-top:12px;">
        <h2 style="color:var(--accent)">All Books (<?php echo count($allTitles); ?>)</h2>
        <div class="grid" style="margin-top:12px;">
          <?php foreach ($alpha as $t):
            $info = getBookInfo($t, $bookInfo);
            $img = "covers/" . ($info["cover"] ?? "default.jpg");
          ?>
          <div class="book-card">
            <div class="book-cover"><img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($t); ?> cover"></div>
            <div class="book-title"><a href="#" class="open-modal" data-book="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></a></div>
            <div class="book-author"><?php echo htmlspecialchars($info["author"] ?? "Unknown"); ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    <?php elseif ($page === 'about'): ?>

      <div style="margin-top:12px; padding:16px; border-radius:10px; background: linear-gradient(180deg, rgba(255,255,255,0.01), transparent); box-shadow:0 12px 40px rgba(0,0,0,0.6);">
        <h2 style="margin-top:0; color:var(--accent);">About CJ's Library</h2>
        <p style="color:var(--muted)">CJ's Library is a small personal digital library showcasing a curated collection focused on nature, travel, and adventure. It demonstrates recursion for categories, a hash table for metadata, and a binary search tree for quick title lookups — all preserved in a single-file PHP site with an accessible, animated Dark Forest design.</p>
      </div>

    <?php elseif ($page === 'contacts'): ?>

      <div style="margin-top:12px; padding:16px; border-radius:10px; background: linear-gradient(180deg, rgba(255,255,255,0.01), transparent); box-shadow:0 12px 40px rgba(0,0,0,0.6);">
        <h2 style="margin-top:0; color:var(--accent)">Contact Us</h2>
        <p style="color:var(--muted)">Have a question or suggestion? Send a message and it'll be appended to a simple log file (contacts.txt).</p>

        <?php if ($contactMsg !== ''): ?>
          <div style="margin-top:12px;padding:10px;background:rgba(255,255,255,0.01);border-radius:8px"><?php echo htmlspecialchars($contactMsg); ?></div>
        <?php endif; ?>

        <form method="post" action="?page=contacts" style="margin-top:12px;">
          <input type="hidden" name="action" value="contact">
          <div style="margin-bottom:8px"><input type="text" name="name" placeholder="Your name" style="width:100%;padding:10px;border-radius:6px;border:1px solid rgba(255,255,255,0.03);background:transparent;color:var(--text)" required></div>
          <div style="margin-bottom:8px"><input type="email" name="email" placeholder="Email" style="width:100%;padding:10px;border-radius:6px;border:1px solid rgba(255,255,255,0.03);background:transparent;color:var(--text)" required></div>
          <div style="margin-bottom:8px"><textarea name="message" placeholder="Message" rows="5" style="width:100%;padding:10px;border-radius:6px;border:1px solid rgba(255,255,255,0.03);background:transparent;color:var(--text)" required></textarea></div>
          <div><button type="submit" style="padding:10px 14px;background: linear-gradient(180deg,#3a6b3f,#274b31);border:none;color:var(--accent);border-radius:8px;cursor:pointer">Send</button></div>
        </form>
      </div>

    <?php else: ?>

      <div style="margin-top:12px;"><h2>Page not found</h2></div>

    <?php endif; ?>

    <div class="footer">&copy; <?php echo date('Y'); ?> CJ's Library. All rights reserved.</div>
  </main>

</div>

<!-- Modal -->
<div class="modal" id="bookModal" aria-hidden="true">
  <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="mTitle">
    <span class="close" aria-label="Close">&times;</span>
    <h3 id="mTitle"></h3>
    <img id="mCover" src="" alt="">
    <p id="mAuthor"></p>
    <p id="mYear"></p>
    <p id="mGenre"></p>
  </div>
</div>

<script>
// keep existing bookData + modal behavior intact
const bookData = <?php echo json_encode($bookInfo, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

const modal = document.getElementById('bookModal');
const mTitle = document.getElementById('mTitle');
const mCover = document.getElementById('mCover');
const mAuthor = document.getElementById('mAuthor');
const mYear = document.getElementById('mYear');
const mGenre = document.getElementById('mGenre');

document.querySelectorAll('.open-modal').forEach(a=>{
  a.addEventListener('click', e=>{
    e.preventDefault();
    const book = a.dataset.book;
    const info = bookData[book];
    mTitle.textContent = book;
    if(info){
      mCover.src = 'covers/' + (info.cover || 'default.jpg');
      mAuthor.textContent = "Author: " + (info.author || 'Unknown');
      mYear.textContent = "Year: " + (info.year || '');
      mGenre.textContent = "Genre: " + (info.genre || '');
    } else {
      mCover.src = '';
      mAuthor.textContent = 'No info';
      mYear.textContent = '';
      mGenre.textContent = '';
    }
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden','false');
  });
});
document.querySelector('.close').onclick = ()=> { modal.style.display='none'; modal.setAttribute('aria-hidden','true'); };
window.onclick = e => { if (e.target==modal) { modal.style.display='none'; modal.setAttribute('aria-hidden','true'); } }
</script>
</body>
</html>
