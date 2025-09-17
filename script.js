const postsContainer = document.getElementById('postsContainer');
const postButton = document.getElementById('postButton');

// サンプル投稿データ（複数）
const samplePosts = [
  {
    title: "タイトルテスト",
    pdfImage: "",
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
    <img src="${post.pdfImage}" alt="PDF画像" />
    <div class="post-content">
      <div class="post-title">${post.title}</div>
    </div>
    <div class="audio-icon" title="音声を聞く">${post.audioIcon}</div>
  `;

  // 音声アイコン押下時の動作（ここではアラート）
  postElem.querySelector('.audio-icon').addEventListener('click', () => {
    alert(`音声再生：${post.title}`);
  });

  postsContainer.appendChild(postElem);
}

// 初期投稿を表示
samplePosts.forEach(addPost);

// 投稿ボタンを押せるようにする（クリックイベント）
postButton.addEventListener('click', () => {
  alert('投稿ボタンがクリックされました！');
  // ここに投稿フォームの表示などの処理を追加可能
});
