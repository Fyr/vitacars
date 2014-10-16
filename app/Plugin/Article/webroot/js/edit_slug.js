function article_onChangeTitle() {
	if (!slug_EditMode) {
		$('#ArticlePageId').val(translit($('#ArticleTitle').val()));
	}
}

function article_onChangeSlug() {
	slug_EditMode = ($('#ArticlePageId').val() && true);
}

function translit(str) {
	return ru2en.tr_url(str);
}
