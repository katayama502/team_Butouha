const postsContainer = document.getElementById('postsContainer');
const postButton = document.getElementById('postButton');
const pageCategory = document.body?.dataset.category || '';

const POSTS_ENDPOINT = postsContainer?.dataset.endpoint || 'posts.php';
const PDF_PLACEHOLDER = 'https://via.placeholder.com/120x160.png?text=PDF';
let currentAudio = null;
let currentAudioButton = null;

function resetCurrentAudio() {
  if (currentAudio) {
    currentAudio.pause();
    currentAudio.currentTime = 0;
  }
  if (currentAudioButton) {
    currentAudioButton.textContent = '🔊';
  }
  currentAudio = null;
  currentAudioButton = null;
}

function toggleAudioPlayback(audio, button) {
  if (!audio) {
    return;
  }

  if (currentAudio && currentAudio !== audio) {
    resetCurrentAudio();
  }

  if (audio.paused) {
    audio.play().then(() => {
      currentAudio = audio;
      currentAudioButton = button;
      button.textContent = '⏸';
    }).catch(() => {
      button.disabled = true;
      button.textContent = '⚠️';
      button.title = '音声を再生できません。';
    });
  } else {
    audio.pause();
    audio.currentTime = 0;
    button.textContent = '🔊';
    currentAudio = null;
    currentAudioButton = null;
  }
}

function createThumbnailElement(pdfPath) {
  const thumbnailWrapper = document.createElement(pdfPath ? 'a' : 'div');
  if (pdfPath) {
    thumbnailWrapper.href = pdfPath;
    thumbnailWrapper.target = '_blank';
    thumbnailWrapper.rel = 'noopener';
    thumbnailWrapper.className = 'post-thumbnail';
  }

  const thumbnail = document.createElement('img');
  thumbnail.src = PDF_PLACEHOLDER;
  thumbnail.alt = pdfPath ? 'PDFを開く' : 'PDFは登録されていません';
  thumbnailWrapper.appendChild(thumbnail);

  return thumbnailWrapper;
}

function createPostElement(post) {
  const postElem = document.createElement('div');
  postElem.className = 'post';

  const thumbnail = createThumbnailElement(post.pdf_path || post.pdfPath);
  postElem.appendChild(thumbnail);

  const content = document.createElement('div');
  content.className = 'post-content';

  const title = document.createElement('div');
  title.className = 'post-title';
  title.textContent = post.title || 'タイトル未設定';
  content.appendChild(title);

  if (post.created_at || post.createdAt) {
    const meta = document.createElement('div');
    meta.className = 'post-meta';
    const createdAtValue = post.created_at || post.createdAt;
    const createdDate = new Date(createdAtValue.replace(' ', 'T'));
    if (!Number.isNaN(createdDate.getTime())) {
      meta.textContent = `投稿日：${createdDate.toLocaleString('ja-JP')}`;
    } else {
      meta.textContent = `投稿日：${createdAtValue}`;
    }
    content.appendChild(meta);
  }

  if (post.pdf_path || post.pdfPath) {
    const pdfLink = document.createElement('a');
    pdfLink.href = post.pdf_path || post.pdfPath;
    pdfLink.target = '_blank';
    pdfLink.rel = 'noopener';
    pdfLink.className = 'post-link';
    pdfLink.textContent = 'PDFを開く';
    content.appendChild(pdfLink);
  }

  postElem.appendChild(content);

  const audioButton = document.createElement('button');
  audioButton.className = 'audio-icon';
  audioButton.type = 'button';

  const audioPath = post.audio_path || post.audioPath;
  if (audioPath) {
    const audio = new Audio(audioPath);
    audio.preload = 'none';
    audio.addEventListener('ended', () => {
      audioButton.textContent = '🔊';
      if (currentAudio === audio) {
        currentAudio = null;
        currentAudioButton = null;
      }
    });
    audio.addEventListener('pause', () => {
      if (currentAudio === audio && audio.paused) {
        audioButton.textContent = '🔊';
        currentAudio = null;
        currentAudioButton = null;
      }
    });
    audio.addEventListener('error', () => {
      audioButton.disabled = true;
      audioButton.textContent = '⚠️';
      audioButton.title = '音声ファイルを読み込めませんでした。';
      if (currentAudio === audio) {
        currentAudio = null;
        currentAudioButton = null;
      }
    });
    audioButton.textContent = '🔊';
    audioButton.title = '音声を再生';
    audioButton.addEventListener('click', () => toggleAudioPlayback(audio, audioButton));
  } else {
    audioButton.textContent = '—';
    audioButton.disabled = true;
    audioButton.title = '音声は登録されていません';
  }

  postElem.appendChild(audioButton);

  return postElem;
}

function showMessage(message, className = 'post-message') {
  if (!postsContainer) {
    return;
  }

  postsContainer.innerHTML = '';
  const messageElem = document.createElement('p');
  messageElem.className = className;
  messageElem.textContent = message;
  postsContainer.appendChild(messageElem);
}

async function loadPosts() {
  if (!postsContainer) {
    return;
  }

  showMessage('投稿を読み込んでいます...');

  try {
    const response = await fetch(POSTS_ENDPOINT, { cache: 'no-store' });
    if (!response.ok) {
      throw new Error(`サーバーエラー (${response.status})`);
    }

    const payload = await response.json();
    if (payload.error) {
      const details = payload.details ? ` (${payload.details})` : '';
      throw new Error(`${payload.error}${details}`);
    }

    const posts = Array.isArray(payload.posts) ? payload.posts : [];

    postsContainer.innerHTML = '';
    if (posts.length === 0) {
      showMessage('投稿はまだありません。最初の投稿を追加してみましょう。');
      return;
    }

    posts.forEach((post) => {
      postsContainer.appendChild(createPostElement(post));
    });
  } catch (error) {
    showMessage(`投稿の読み込みに失敗しました: ${error.message}`, 'post-message error');
  }
}

if (postsContainer) {
  loadPosts();
}

function buildPostFormUrl(baseUrl, categoryKey) {
  if (!categoryKey) {
    return baseUrl;
  }

  try {
    const url = new URL(baseUrl, window.location.href);
    if (!url.searchParams.has('category')) {
      url.searchParams.set('category', categoryKey);
    }
    return url.href;
  } catch (error) {
    if (baseUrl.includes('category=')) {
      return baseUrl;
    }

    const separator = baseUrl.includes('?') ? '&' : '?';
    return `${baseUrl}${separator}category=${encodeURIComponent(categoryKey)}`;
  }
}

if (postButton) {
  postButton.addEventListener('click', () => {
    const target = postButton.dataset.targetForm || 'post_form.php';
    const destination = buildPostFormUrl(target, pageCategory);

    if (pageCategory) {
      try {
        sessionStorage.setItem('selectedCategory', pageCategory);
      } catch (storageError) {
        // storage が利用できない環境では何もしません
      }
    } else {
      try {
        sessionStorage.removeItem('selectedCategory');
      } catch (removeError) {
        // storage が利用できない環境では何もしません
      }
    }

    window.location.href = destination;
  });
}
