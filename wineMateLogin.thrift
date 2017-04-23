namespace java co.tagtalk.winemate.thriftfiles
namespace php wineMateThrift

// Registration and login (A1)

enum RegistrationStatus {
	REGISTRATION_SUCCESS = 1,
	REGISTRATION_DUPLICATE_USERNAME = 2,
	REGISTRATION_DUPLICATE_EMAIL = 3,
	REGISTRATION_INVALID_INPUT = 4,
}

enum FindPasswordStatus {
	PW_SUCCESS = 1,
	PW_FAILED  = 2,
}

enum LoginStatus {
	LOGIN_SUCCESS = 1,
	LOGIN_FAILED  = 2,
	LOGIN_UNACTIVATED = 3,
}

struct LoginResult {
	1: LoginStatus status,
	2: i32 userId,
}

enum ThirdParty {
	NONE = 0,
	WECHAT = 1,
}

struct User {
	1: string userName,
	2: string email,
	3: string password,
	4: string lastName,
	5: string firstName,
	6: string sex,
	7: i32	  age,
	8: i32    yearOfBirth,
	9: i32    monthOfBirth,
	10: i32    dayOfBirth,
	11: i32 rewardPoints,
	12: string photoUrl,
	13: ThirdParty thirdParty,
}

// Authentication (A2)

struct TagInfo {
	1: string tagID,
	2: string secretNumber,
	3: CountryId countryId,
	4: string date,
	5: string time,
	6: string city,
	7: string detailedLocation,
}

struct WineInfo {
	1: bool   	isGenuine,
	2: bool   	isSealed,
	3: string 	wineName,
	4: i32		wineId,
	5: string	winePicURL,
	6: double 	wineRate,
	7: string openedTime,
	8: string wineryName,
	9: string regionName,
	10: string year,
	11: string openedCity, // not shown on UI
	12: string openedCountry,
	13: string wechatShareUrl,
	14: i32		rewardPoint,
	15: string wineryLogoPicUrl,
	16: string wineryNationalFlagUrl,
}

enum CountryId {
	ENGLISH = 1,
 	CHINESE = 2,
}

struct BottleOpenInfo {
	1: string tagId,
	2: i32 wineId,
	3: i32 userId,
	4: string bottleOpenIdentifier, // should match db value
	5: string date,
	6: string time,
	7: string city,
	8: string detailedLocation,
	9: string country,
}

// WineInfo (A3)

enum ReviewerSex {
	MALE = 1,
	FEMALE = 2,
}

struct FoodParingPics {
	1: string picName,
	2: string picUrl,
}

struct WineBasicInfoRequest {
	1: i32 wineId,
	2: i32 countryId,
}

struct WineBasicInfoResponse {
	1:  string wineName,
	2:  string wineryName,
	3:  string location,
	4:  string nationalFlagUrl,
	5:  string theWineInfo,
	6:  string foodPairingInfo,
	7:  string cellaringInfo,
	8:  list<FoodParingPics> foodParingPics,
	9:  string regionName,
	10: string regionInfo,
	11: string wineryWebsiteUrl, // need to look at how to open a new browser
	12: string wineryLogoPicUrl,
	13: string grapeInfo,
	14: string averagePrice,
	15: string wechatShareUrl,
	16: string year,
}

struct WineReviewAndRatingReadRequest {
	1: i32 wineId,
	2: i32 userId,
}

struct WineReviewAndRatingData {
	1: string reviewerUserName,
	2: double rate,
	3: string timeElapsed,
	4: string reviewContent,
	5: ReviewerSex sex,
	6: i32 userId,
	7: bool isFollowed,
	8: bool isMyFriend,
	9: string photoUrl,
	10: ThirdParty thirdParty;
	11: string reviewerFirstName;
}

struct WineReviewAndRatingReadResponse {
	1: list<WineReviewAndRatingData> data,  // 100
	2: i32 numOfRating,
	3: i32 numOfReview,
	4: double averageRate,
}

struct WineReviewAndRatingWriteRequest {
	1: i32 wineId,
	2: i32 userId,
	3: double score,
	4: string reviewContent,
	5: string date,
	6: string time,
}

struct WineReviewAndRatingWriteResponse {
 	1: bool isSuccess,
}

struct MyRateRecordRequest {
	1: i32 userId,
	2: i32 wineId,
}

struct MyRateRecordResponse {
	1: bool alreadyRated,
	2: double myRate = 0.0,
}

// My bottle

struct BottleInfo {
	1: i32 wineId,
	2: string wineName,
	3: string regionName,
	4: string openDate,
	5: string openTime,
	6: string openCity,
	7: string winePicUrl,
	8: string nationalFlagUrl,
	9: double myRate = 0, // 0 or null for OpenedBottlesRequest and ScannedBottlesRequest
	10: string year,
	11: string wineryName,
	12: double averageRate = 0,
}

struct MyBottlesRequest {
	1: i32 userId,
	2: i32 countryId,
}

struct OpenedBottlesResponse {
	1: i32 openedNumber,
	2: i32 ratedNumber,
	3: i32 scannedNumber,
	4: i32 wishListSize,
	5: i32 totalWinesNumber,
	6: ReviewerSex sex,
	7: BottleInfo currentOpenedBottleInfo,
	8: list<BottleInfo> openedBottleHistory,
	9: string photoUrl,
}

struct ScannedBottlesResponse {
	1: list<BottleInfo> scannedBottleHistory,
}

struct RatedBottlesResponse {
	1: list<BottleInfo> ratedBottleHistory,
}

struct AllBottlesRequest {
    1: i32 userId,
    2: i32 countryId,
}

struct AllBottlesResponse {
    1: list<BottleInfo> allBottles,
}
/////////////////////////////////////////////
// NewsFeed

struct NewsFeedRequest {
	1: i32 userId,
	2: CountryId countryId,
}

enum FeedType {
	SYSTEMFEED = 1,
	USERFEED = 2,
	USERRATE = 3,
}

struct NewsFeedData {
	1: FeedType feedType,
	2: string authorName,
	3: string feedTitle, // e.g, Yao Liu posted
	4: string contentTitle, // the real title of the content
	5: string contentAbstract,
	6: string date,
	7: string picUrl,
	8: string contentUrl,
	9: string authorUrl,
	10: BottleInfo bottleInfo,
	11: i32 userid,
}

struct NewsFeedResponse {
	1: list<NewsFeedData> response,
}

struct WechatLoginInfo {
	1: string openId,
	2: string unionId,
	3: string originJsonFromWechat,
	4: string accessToken,
}

struct WineryInfoRequest {
	1: string wineryName,
	2: i32 countryId,
}

struct WineryInfoResponseSingleItem {
	1: i32 wineId,
	2: string wineName,
	3: string winePicUrl,
	4: string year,
}

struct WineryInfoSingleContent {
	1: string title,
	2: string briefText,
	3: string url,
}

struct WineryInfoResponse {
	1: list<WineryInfoResponseSingleItem> wineryWineList,
	2: list<WineryInfoSingleContent> wineryInfoContents,
	3: list<string> wineryPhotoUrls,
	4: string wineryWebsiteUrl,
}

struct FriendListRequest {
	1: i32 userId,
}

struct FriendInfo {
	1: i32 userId,
	2: string userName,
	3: string sex,
	4: i32 ratingNumbers, // currently not used
	5: bool isFollowing,  // if I follow him/her
	6: bool isFollowed,   // if he follows me
	7: string lastName,
	8: string firstName,
	9: string photoUrl,
	10: ThirdParty thirdParty,
}

struct FriendListResponse {
	1: list<FriendInfo> friendList,
}

struct MyFollowingListResponse {
	1: list<FriendInfo> myFollowingList,
}

struct MyFollowersListResponse {
	1: list<FriendInfo> myFollowersList,
}

struct MyProfile {
	1: User user,
	2: i32 followerNumber,
	3: i32 followingNumber,
	4: i32 ratedNumber,
	5: bool isFollowing, //if I follow him/her
	6: bool isFollowed, //if he/she follows me
	7: bool hideProfileToStranger,
	8: i32 wishlistNumber,
}

// Rewards program
enum UserActions {
	ShareWineInfoToWechat = 1,
	OpenedBottle = 2,
	ShareWineryInfoToWechat = 3,
	ShareWineryMemberShipToWechat = 4,
}

enum AddRewardPointsResponse {
	Success = 1,
	AlreadyEarned = 2,
}

struct AddRewardPointsRequest {
	1: i32 userId,
	2: UserActions useAction,
	3: i32 wineId, //only valid for 'OpenedBottle' action, 0 for other actions
}

// User Photo
struct UserPhotoResponse{
	1: string userPhotoUrl,
	2: bool alreadyUploaded,
	3: string receiverScriptUrl,
}

// Wish List
struct MyWishListResponse {
	1: bool success,
	2: list<BottleInfo> wishList,
}
struct AddToWishlistRequest {
	1: i32 userId,
	2: i32 wineId,
	3: bool enabled,
}
struct IsInWishlistResponse {
	1: bool success,
	2: bool isInList,
}

struct RewardSingleItem {
	1: i32 wineId,
	2: string wineName,
	3: string winePicUrl,
	4: string year,
	5: string region,
	6: i32 points,
	7: bool outOfStock, //True if out of stock
}

struct RewardItemRequest {
	1: i32 userId,
	2: string wineryName,
	3: CountryId countryId,
}

struct RewardItemResponse {
	1: i32 currentPoints,
	2: list<RewardSingleItem> rewardItemList,
}

struct Address {
	1: string province,
	2: string city,
	3: string street,
	4: string zipCode,
	5: string phoneNumber,
	6: string email, // optional
	7: string fullName,
	8: string country,
}

struct RewardRedeemSingleItem {
	1: i32 wineId,
	2: i32 quantity,
	3: i32 points, //For Android end use only
}

struct RewardRedeemRequest {
	1: i32 userId,
	2: list<RewardRedeemSingleItem> RewardRedeemItems
	3: Address address,
	4: string trackingNumber,
}

enum RewardRedeemResponseCode {
	SUCCESS = 1,
	FAILED = 2,
	ACCOUNT_UNACTIVATED = 3,
}
struct RewardRedeemResponse {
	1: RewardRedeemResponseCode resp_code,
	2: i32 remainingPoints,
}

/////////////////////////////////////////////
service WineMateServices {
	// User Login
	LoginResult login(1: User user),
	LoginResult loginWechat(1: WechatLoginInfo wechatLoginInfo),
	RegistrationStatus registration (1: User user),
	bool sendActivateEmail(1: i32 userId),
	bool activateAccount(1: i32 userId, 2: bool activate),
	FindPasswordStatus findPassword(1: User user),
	
 	WineInfo authentication(1:TagInfo tagInfo, 2:i32 userId),
	bool openBottle(1: BottleOpenInfo bottleOpenInfo),
	WineBasicInfoResponse 	getBasicInfo (1: WineBasicInfoRequest wineBasicInfoRequest),
	WineReviewAndRatingReadResponse	getWineReviewAndRating (1: WineReviewAndRatingReadRequest wineReviewAndRatingReadRequest),
	WineReviewAndRatingWriteResponse writeWineReviewAndRating (1: WineReviewAndRatingWriteRequest wineReviewAndRatingWriteRequest),
	MyRateRecordResponse getMyRateRecord (1: MyRateRecordRequest myRateRecordRequest),
	//MyBottlesResponse getMyBottles (1: MyBottlesRequest myBottlesRequest),
	OpenedBottlesResponse getMyOpenedBottles (1: MyBottlesRequest myBottlesRequest),
	ScannedBottlesResponse getMyScannedBottles (1: MyBottlesRequest myBottlesRequest),
	RatedBottlesResponse getMyRatedBottles (1: MyBottlesRequest myBottlesRequest),
	NewsFeedResponse getMyNewsFeed (1: NewsFeedRequest newsFeedRequest),
	WineryInfoResponse getWineryInfo (1: WineryInfoRequest wineryInfoRequest),
	
	void followUser(1:i32 user, 2:i32 userToFollow),
	void unfollowUser(1:i32 user, 2:i32 userToUnfollow),
	void addFriend(1:i32 user1, 2:i32 user2),
	FriendListResponse getFriendList(1: FriendListRequest friendListRequest),
	FriendListResponse searchFriend(1:string friendPrefix);
	
	string getTagPassword(1: string tagId),
	MyProfile getMyProfile(1: i32 requesterId, 2: i32 requestedId),
	bool updateMyProfile (1: User user),
	MyFollowingListResponse getMyFollowingList (1: FriendListRequest followingListRequest),
	MyFollowersListResponse getMyFollowersList (1: FriendListRequest followersListRequest),
	bool setPrivacy(1: i32 userId, 2: bool hideProfileToStranger),
	AddRewardPointsResponse addRewardPoints(1: AddRewardPointsRequest addRewardPointsRequest),
	i32 getMyRewardPoints(1: i32 userId),
	RewardItemResponse getRewardItemList(1: RewardItemRequest rewardItemRequest),
	RewardRedeemResponse rewardRedeem(1: RewardRedeemRequest rewardRedeemRequest),
	UserPhotoResponse getUserPhoto(1: i32 userId ),

	// Wish List
	MyWishListResponse getMyWishlist(1: MyBottlesRequest myBottlesRequest),
	bool addToWishlist(1: AddToWishlistRequest addToWishlistRequest),
	IsInWishlistResponse isInWishlist(1: i32 userId, 2: i32 wineId),
	
	AllBottlesResponse getAllBottles(1: AllBottlesRequest allBottlesRequest),
}
