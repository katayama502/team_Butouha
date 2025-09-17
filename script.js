const postsContainer = document.getElementById('postsContainer');
const postButton = document.getElementById('postButton');

// サンプル投稿データ（複数）
const samplePosts = [
  {
    title: "タイトルテスト",
    pdfImage: "https://via.placeholder.com/120x160.png?text=PDF",
    audioIcon: "🔊"
  },
  {
    title: "タイトル２",
    pdfImage: "https://via.placeholder.com/120x160.png?text=PDF",
    audioIcon: "🎧"
  },
  {
    title: "タイトル３",
    pdfImage: "https://via.placeholder.com/120x160.png?text=PDF",
    audioIcon: "🔉"
  }
];

// 投稿を画面に追加する関数
function addPost(post) {
  const postElem = document.createElement('div');
  postElem.className = 'post';

  postElem.innerHTML = `
    <img src="${post.pdfImage}" alt="PDFのサムネイル" />
    <div class="post-content">
      <div class="post-title">${post.title}</div>
    </div>
    <button class="audio-icon" type="button" title="音声を聞く">${post.audioIcon}</button>
  `;

  // 音声アイコン押下時の動作（ここではアラート）
  postElem.querySelector('.audio-icon').addEventListener('click', () => {
    alert(`音声再生：${post.title}`);
  });

  postsContainer.appendChild(postElem);
}

if (postsContainer) {
  // 初期投稿を表示
  samplePosts.forEach(addPost);
}

if (postButton) {
  // 投稿ボタンを押したらフォームページに遷移
  postButton.addEventListener('click', () => {
    window.location.href = 'style.html';
  });
}
