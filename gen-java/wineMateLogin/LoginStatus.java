/**
 * Autogenerated by Thrift Compiler (1.0.0-dev)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 *  @generated
 */
package wineMateLogin;


import java.util.Map;
import java.util.HashMap;
import org.apache.thrift.TEnum;

public enum LoginStatus implements org.apache.thrift.TEnum {
  LOGIN_SUCCESS(0),
  LOGIN_FAILED(1);

  private final int value;

  private LoginStatus(int value) {
    this.value = value;
  }

  /**
   * Get the integer value of this enum value, as defined in the Thrift IDL.
   */
  public int getValue() {
    return value;
  }

  /**
   * Find a the enum type by its integer value, as defined in the Thrift IDL.
   * @return null if the value is not found.
   */
  public static LoginStatus findByValue(int value) { 
    switch (value) {
      case 0:
        return LOGIN_SUCCESS;
      case 1:
        return LOGIN_FAILED;
      default:
        return null;
    }
  }
}