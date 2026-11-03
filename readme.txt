/*
 * ===================================================================
 * Snippet: Navigator
 * ===================================================================
*  Version History:
*  0.1   - initial release
*  0.1.1 - converted to be PHP 4 compatible
*  0.1.2 - fixed a bug affecting skipping of unpublished documents
*  0.1.3 - fixed bug affecting the use of templates stored in files and in chunks.
*  1.0.3 - A magic jump in version number
*  1.0.4 - added isFolderAction (by Nicola1971)
*  1.0.5 - PHP 8.x compatibility (by Nicola1971):
 * ===================================================================
 * About:
 * ===================================================================
 * This templatable snippet is designed to aid navigation, by providing information
 * about a related page. Currently, this snippet handles the following relationships:
 *
 * - next (next);
 * - previous (prev); and
 * - parent (up).
 *
 * Unlike PrevJumpNext, where next and prev correspond to siblings within the same
 * folder, Navigator will transcend the levels of the menu system if you want too
 * to find an adjacent document in the document tree.
 *
 * This snippet is similar to PrevJumpNext, but there are some things that
 * PrevJumpNext does that this wont do. 'Jump' is one of them.
 * Ordering by something other than menuindex is also currently not supported,
 * since Navigator is really designed for navigating menu systems.
 *
 * ===================================================================
 * Installation
 * ===================================================================
 * 1. Copy the navigator folder into assets/snippets/
 * 2. In modx, go to Resources->Manage Resources->Snippets.
 * 3. Create a new snippet and enter the following details:
 *    Snippet Name:
 *      Navigator
 *    Description:
 *      <strong>1.0.3</strong> Enables navigation to related pages (prev, next, up)
 * 4. Copy the contents of navigator.snippet.php into the Snippet Code box and
 *    save the snippet.
 *
 * ===================================================================
 * Usage:
 * ===================================================================
 * To use this snippet you'll have to provide a template for the information you
 * want to display about the related page.
 * Within the template you can use:
 * - modx document variable placeholders [+id+], [+pagetitle+], [+description+], etc...
 * - template variable placeholders [+mytv+]
 * - Navigator placeholders
 *
 * Currently, the only navigator placeholder available is  [+nav.rel+], which
 * returns the relationship with the document: 'next', 'prev', or 'up'. See the
 * &rel input parameter.
 *
 * ===================================================================
 * Example templates and snippet calls:
 * ===================================================================
 *
 * Provide links to related pages using the link element:
 * LinkChunk: <link rel="[+nav.rel+]" type="[+contentType+]" href="[(site_url)][~[+id+]~]"></link>
 * [[Navigator? &rel=`prev` &template=`LinkChunk`]]
 * [[Navigator? &rel=`up` &template=`LinkChunk `]]
 * [[Navigator? &rel=`next` &template=`LinkChunk `]]
 *
 * Provide links to related pages using a hyperlink:
 * PrevChunk: <a class="navlink" rel="[+nav.rel+]" type="[+contentType+]" href="[(site_url)][~[+id+]~]">Prev</a>
 * [[Navigator? &rel=`prev` &template=`PrevChunk `]]
 *
 * ======================================================
 * Configuration Settings
 * ======================================================
 *
 * &rel [string]
 * The relationship that the document to be found has to the current document:
 * 'next', 'prev', 'up'
 * Default: 'up'
 *
 * &stopIds [string]
 * A comma separated list of document ids.
 * If the id of the related document appears in this list, the empty string is returned.
 * This is useful for specifying starts and ends of sequences of documents
 * Default: ''
 *
 * &offIds [string]
 * A comma separated list of document ids.
 * If the current document is in this list, the empty string is returned.
 * Provides a means of turning off the snippet for certain documents
 * Default: ''
 *
 * &transcend [number]
 * Whether or not to transcend levels
 * when searching for the next and previous documents
 * Default: 1
 *
 * &weblinkAction [string]
 * How to behave when a weblink is encountered.
 * Possibilities are 'link', 'skip', 'stop'
 * Default: 'skip'
 *
 * &unpublishedAction [string]
 * How to behave when an unpublished document is encountered.
 * Possibilities are 'link', 'skip', 'stop'
 * Default: 'skip'
 *
 * &notInMenuAction [string]
 * How to behave when a document is encountered that does not appear in the menu.
 * Possibilities are 'link', 'skip', 'stop'
 * Default: 'skip'
 *
 * &isFolderAction [string]
 * How to behave when a document is encountered is a folder.
 * Possibilities are 'link', 'skip', 'stop'
 * Default: 'skip'
 *
 * &template[string]
 * A template for the output
 * Use [+nav.rel+] to return the type of link (prev, next, parent)
 * Default: ''
 *
 * ===================================================================
 * Impovements:
 * ===================================================================
 * This snippet works in a brute force and possible quite inefficient way
 * when finding the related document and when skipping unwanted documents.
 * Thanks to modx's caching mechanism, it's not a big issue however.
 *
 * By rolling up the logic, which currently involves iteration and multiple modx
 * api calls into fewer mysql queries it could probably be made more efficient.
 * If anyone wants to have a go, feel free
 *
 * ===================================================================
 * The snippet...
 * ===================================================================
*/
