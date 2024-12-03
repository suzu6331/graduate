import requests
from bs4 import BeautifulSoup, NavigableString, Tag
import json
from github import Github, GithubException
from urllib.parse import urljoin
import os

# 環境変数からトークンを読み込む
GITHUB_TOKEN = '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'

REPO_NAME = 'suzu6331/graduate'
COMMIT_MESSAGE = 'Add scraped questions'

# ローカルの保存パス
LOCAL_BASE_PATH = r'C:\Users\user\OneDrive - 学校法人　電波学園\デスクトップ\graduate-main\AP'
LOCAL_IMAGE_PATH = os.path.join(LOCAL_BASE_PATH, 'image')

# GitHubの保存パス（test.pyと同じディレクトリ）
GITHUB_BASE_DIR = 'AP'
GITHUB_IMAGE_DIR = os.path.join(GITHUB_BASE_DIR, 'image')

# 年度と試験区分の取得
def get_file_name(year, exam_type, index):
    # 秋だったら101,102,,,と保存する
    if exam_type == 'aki':
        index = 100 + index
    # それ以外は001,002,,,と保存する
    return f"{year}{str(index).zfill(3)}"

def fetch_questions(url, file_id, local_year_path, local_image_path):
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)'
                      ' Chrome/58.0.3029.110 Safari/537.3'
    }
    response = requests.get(url, headers=headers)
    if response.status_code != 200:
        print(f"ページ取得失敗: {url} (ステータスコード: {response.status_code})")
        return []

    response.encoding = 'utf-8'
    soup = BeautifulSoup(response.text, 'html.parser')

    # 'mondai'を探す（タグ名に依存しない）
    mondai_ele = soup.find(id='mondai')
    ans_ele = soup.find('div', class_='ansbg')

    if not mondai_ele or not ans_ele:
        print(f"問題または回答が見つかりませんでした: {url}")
        # デバッグ用HTMLの保存
        debug_dir = os.path.join(local_year_path, 'debug')
        os.makedirs(debug_dir, exist_ok=True)
        debug_file = os.path.join(debug_dir, f"{file_id}.html")
        with open(debug_file, 'w', encoding='utf-8') as f:
            f.write(response.text)
        print(f"デバッグ用HTMLを保存しました: {debug_file}")
        return []

    # 質問を保存するリストを初期化
    questions = []

    # mondai_html を元のHTMLの構造に基づいて構築
    mondai_html_parts = []
    image_index = 0  # 画像のインデックスを追跡

    for content in mondai_ele.contents:
        if isinstance(content, NavigableString):
            text = content.strip()
            if text:
                mondai_html_parts.append(text)
        elif isinstance(content, Tag):
            if content.name == 'div' and 'img_margin' in content.get('class', []):
                img_tag = content.find('img')
                if img_tag and img_tag.get('src'):
                    img_url = img_tag['src']
                    absolute_img_url = urljoin(url, img_url)
                    img_filename = f"{file_id}-{image_index}.jpg"
                    # 保存およびGitHubアップロード
                    save_image(absolute_img_url, img_filename, local_image_path)
                    # imgタグの src を置き換える
                    mondai_html_parts.append(f'<div class="img_margin"><img data-image-index="{image_index}"></div>')
                    image_index += 1
            else:
                # その他のタグがあれば、そのままテキストとして追加
                text = content.get_text(separator="\n", strip=True)
                if text:
                    mondai_html_parts.append(text)

    # 問題文の組み立て
    mondai_html = ''.join(mondai_html_parts)

    # 選択肢と回答
    sentaku = []
    kaitou = None
    sentaku_image_index = 0  # sentaku用のインデックスを初期化

    for li_index, li in enumerate(ans_ele.find_all('li')):
        img_tag = li.find('img')
        if img_tag:
            img_url = img_tag['src']
            absolute_img_url = urljoin(url, img_url)
            img_filename = f"{file_id}-{image_index}.gif"
            img_tag['src'] = img_filename
            if len(ans_ele.find_all('li')) == 1:
                kaitou = f"<img data-image-index=\"{sentaku_image_index}\">"
            else:
                sentaku.append(f"<img data-image-index=\"{sentaku_image_index}\">")
            save_image(absolute_img_url, img_filename, local_image_path)
            image_index += 1
            sentaku_image_index += 1  # sentaku用のインデックスをインクリメント
        else:
            # sentakuのアイウエを削除して選択肢の文字列だけを保存
            choice_text = li.get_text(separator="\n", strip=True)
            if choice_text and choice_text[0] in "アイウエ":
                choice_text = choice_text[1:].strip()
            sentaku.append(choice_text)

    # 正解の回答を抽出
    ans_span = soup.find('span', id='answerChar')
    if ans_span:
        ans_text = ans_span.text.strip()
        print(f"Found answer text: {ans_text}")
        ans_map = {"ア": 0, "イ": 1, "ウ": 2, "エ": 3}
        ans = ans_map.get(ans_text)
    else:
        ans = None  # NULLをいれる

    # 質問保存
    question = {
        "id": file_id,
        "mondai": mondai_html
    }

    # 選択肢がある場合はsentakuを追加、ない場合はkaitouを追加
    if kaitou:
        question["kaitou"] = kaitou
    else:
        question["sentaku"] = sentaku

    # 回答保存
    question["answer"] = ans

    questions.append(question)

    return questions


# 画像を保存して、GitHubにアップ
def save_image(img_url, img_filename, local_image_path):
    # ローカルのディレクトリが存在しない場合は作成
    os.makedirs(local_image_path, exist_ok=True)

    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)'
                      ' Chrome/58.0.3029.110 Safari/537.3'
    }
    img_response = requests.get(img_url, headers=headers)
    if img_response.status_code != 200:
        print(f"画像取得失敗: {img_url} (ステータスコード: {img_response.status_code})")
        return

    image_save_path = os.path.join(local_image_path, img_filename)
    with open(image_save_path, 'wb') as img_file:
        img_file.write(img_response.content)
        print(f"画像を保存しました: {image_save_path}")
    upload_image_to_github(img_filename, image_save_path)

# GitHubに画像をアップ
def upload_image_to_github(img_filename, image_save_path):
    try:
        g = Github(GITHUB_TOKEN)
        repo = g.get_repo(REPO_NAME)
        with open(image_save_path, 'rb') as img_file:
            content = img_file.read()
            path = os.path.join(GITHUB_IMAGE_DIR, img_filename).replace("\\", "/")  # GitHubはスラッシュを使用
            try:
                contents = repo.get_contents(path)
                repo.update_file(path, COMMIT_MESSAGE, content, contents.sha)
                print(f"Updated image {path} in GitHub.")
            except GithubException as e:
                if e.status == 404:
                    repo.create_file(path, COMMIT_MESSAGE, content)
                    print(f"Created image {path} in GitHub.")
                else:
                    print(f"Failed to upload image {path} to GitHub. Error: {e.data}")
    except GithubException as e:
        print(f"GitHubへの接続に失敗しました。エラー: {e.data}")

# JSONに保存
def save_to_json(data, filename, local_year_path):
    # ローカルのディレクトリが存在しない場合は作成
    os.makedirs(local_year_path, exist_ok=True)

    json_save_path = os.path.join(local_year_path, os.path.basename(filename))
    with open(json_save_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=4)
    print(f"JSONをローカルに保存しました: {json_save_path}")

# GitHubにディレクトリ作成
def create_github_directory(token, repo_name, directory_path, commit_message):
    try:
        g = Github(token)
        repo = g.get_repo(repo_name)
        # GitHubにはディレクトリ自体を作成するAPIはないため、ダミーファイルを作成
        gitkeep_path = os.path.join(directory_path, '.gitkeep').replace("\\", "/")
        try:
            repo.get_contents(gitkeep_path)
            print(f"Directory {directory_path} already exists in GitHub.")
        except GithubException as e:
            if e.status == 404:
                repo.create_file(gitkeep_path, commit_message, "")
                print(f"Successfully created directory {directory_path} in GitHub.")
            else:
                print(f"Failed to check/create directory {directory_path} in GitHub. Error: {e.data}")
    except GithubException as e:
        print(f"Failed to create directory {directory_path} in GitHub. Error: {e.data}")

# GitHubにアップ
def upload_to_github(token, repo_name, file_path, commit_message, content):
    try:
        g = Github(token)
        repo = g.get_repo(repo_name)

        try:
            contents = repo.get_contents(file_path)
            repo.update_file(file_path, commit_message, content, contents.sha)
            print(f"Successfully updated {file_path} in GitHub.")
        except GithubException as e:
            if e.status == 404:
                repo.create_file(file_path, commit_message, content)
                print(f"Successfully created {file_path} in GitHub.")
            else:
                print(f"Failed to update {file_path} in GitHub. Error: {e.data}")
    except GithubException as e:
        print(f"Failed to upload to GitHub. Error: {e.data}")

def main():
    ##################################################################
    base_url = 'https://www.ap-siken.com/kakomon/28_aki/q'
    num_questions = 80  # 取得する問題数
    year = "2016"
    exam_type = "aki"  # または "haru" もしくは "aki"
    ##################################################################

    # ローカルの年度ディレクトリ
    local_year_path = os.path.join(LOCAL_BASE_PATH, year)
    # GitHubの年度ディレクトリ
    github_year_directory = os.path.join(GITHUB_BASE_DIR, year).replace("\\", "/")

    # ディレクトリ作成（GitHub）
    create_github_directory(GITHUB_TOKEN, REPO_NAME, github_year_directory, COMMIT_MESSAGE)
    create_github_directory(GITHUB_TOKEN, REPO_NAME, GITHUB_IMAGE_DIR, COMMIT_MESSAGE)

    for i in range(1, num_questions + 1):
        url = f"{base_url}{i}.html"
        file_name = get_file_name(year, exam_type, i)
        questions = fetch_questions(url, file_name, local_year_path, LOCAL_IMAGE_PATH)
        if not questions:
            continue  # 質問が取得できなかった場合はスキップ
        json_data = {"quizzes": questions}
        json_file_name = f"{year}/{file_name}.json"

        print(f"Processing: {json_file_name}")

        # JSONをローカルに保存
        save_to_json(json_data, json_file_name, local_year_path)
        
        # GitHubにアップロード
        upload_to_github(
            GITHUB_TOKEN,
            REPO_NAME,
            f"{GITHUB_BASE_DIR}/{json_file_name}".replace("\\", "/"),
            COMMIT_MESSAGE,
            json.dumps(json_data, ensure_ascii=False, indent=4)
        )

if __name__ == "__main__":
    main()

